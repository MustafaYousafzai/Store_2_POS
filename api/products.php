<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

// Enforce login and permission
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        // Allow list to anyone who can view sales, manage inventory, create sales, process exchanges/refunds, or manage khata
        if (!isAdmin() && !hasPermission('VIEW_SALES') && !hasPermission('MANAGE_INVENTORY') && !hasPermission('CREATE_SALE') && !hasPermission('EXCHANGE') && !hasPermission('REFUND') && !hasPermission('MANAGE_KHATA')) {
            sendJSON(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        
        $search = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $where = ["1=1"];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(p.name LIKE ? OR p.barcode LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($category_id > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $category_id;
        }
        
        if (!empty($status)) {
            $where[] = "p.status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(" AND ", $where);
        
        // Get total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get records
        $query = "
            SELECT p.*, c.name as category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereClause
            ORDER BY p.id DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        sendJSON([
            'success' => true,
            'products' => $products,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    else if ($action === 'get_categories' || $action === 'categories') {
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        
        $query = "SELECT * FROM categories";
        $params = [];
        if ($status !== '') {
            $query .= " WHERE status = ?";
            $params[] = $status;
        }
        $query .= " ORDER BY name ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'categories' => $categories]);
    }
    
    else if ($action === 'generate_barcode') {
        requirePermission('MANAGE_INVENTORY');
        
        // Generate a random unique EAN-13-like barcode (start with 200 for internal use)
        $unique = false;
        $barcode = '';
        while (!$unique) {
            $rand = str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
            $barcode = '200' . $rand;
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE barcode = ?");
            $stmt->execute([$barcode]);
            if ($stmt->fetchColumn() == 0) {
                $unique = true;
            }
        }
        
        sendJSON(['success' => true, 'barcode' => $barcode]);
    }
    
    else if ($action === 'get_product_by_barcode') {
        // Staff at POS needs this
        requirePermission('CREATE_SALE');
        
        $barcode = isset($_GET['barcode']) ? trim(sanitize($_GET['barcode'])) : '';
        
        if (empty($barcode)) {
            sendJSON(['success' => false, 'message' => 'Barcode is empty.']);
        }
        
        $stmt = $db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.barcode = ?
        ");
        $stmt->execute([$barcode]);
        $product = $stmt->fetch();
        
        if ($product) {
            sendJSON(['success' => true, 'product' => $product]);
        } else {
            sendJSON(['success' => false, 'message' => 'Product not found.']);
        }
    }

    else if ($action === 'check_barcode') {
        requirePermission('MANAGE_INVENTORY');
        
        $barcode = isset($_GET['barcode']) ? trim(sanitize($_GET['barcode'])) : '';
        $exclude_id = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;
        
        if (empty($barcode)) {
            sendJSON(['success' => true, 'exists' => false]);
        }
        
        $stmt = $db->prepare("
            SELECT p.id, p.name, p.barcode, p.selling_price, p.quantity, c.name AS category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.barcode = ? AND p.id != ? 
            LIMIT 1
        ");
        $stmt->execute([$barcode, $exclude_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            sendJSON([
                'success' => true,
                'exists' => true,
                'product' => [
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'barcode' => $product['barcode'],
                    'selling_price' => (float)$product['selling_price'],
                    'quantity' => (int)$product['quantity'],
                    'category_name' => $product['category_name'] ?? 'Uncategorized'
                ]
            ]);
        } else {
            sendJSON(['success' => true, 'exists' => false]);
        }
    }
} 

else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('MANAGE_INVENTORY');
    
    if ($action === 'add') {
        $name = isset($_POST['name']) ? trim(sanitize($_POST['name'])) : '';
        $barcode = isset($_POST['barcode']) ? trim(sanitize($_POST['barcode'])) : '';
        $cost_price = isset($_POST['cost_price']) ? (float)$_POST['cost_price'] : 0.00;
        $selling_price = isset($_POST['selling_price']) ? (float)$_POST['selling_price'] : 0.00;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
        if ($category_id <= 0) {
            $category_id = null;
        }
        $description = isset($_POST['description']) ? trim(sanitize($_POST['description'])) : '';
        $show_in_pos = isset($_POST['show_in_pos']) ? 1 : 0;
        $low_stock_threshold = isset($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : 10;
        if ($low_stock_threshold <= 0) {
            $low_stock_threshold = 10;
        }
        $status = ($show_in_pos === 1) ? 'active' : 'draft';
        
        if (empty($name)) {
            sendJSON(['success' => false, 'message' => 'Product name is required.'], 400);
        }
        
        // Generate barcode if empty
        if (empty($barcode)) {
            $unique = false;
            while (!$unique) {
                $rand = str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
                $barcode = '200' . $rand;
                $chk = $db->prepare("SELECT COUNT(*) FROM products WHERE barcode = ?");
                $chk->execute([$barcode]);
                if ($chk->fetchColumn() == 0) {
                    $unique = true;
                }
            }
        } else {
            // Validate barcode uniqueness and fetch conflicting product details
            $chk = $db->prepare("
                SELECT p.id, p.name, p.barcode, p.selling_price, p.quantity, c.name AS category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.barcode = ? 
                LIMIT 1
            ");
            $chk->execute([$barcode]);
            $existingProduct = $chk->fetch();
            if ($existingProduct) {
                $categoryText = !empty($existingProduct['category_name']) ? " (Category: {$existingProduct['category_name']})" : "";
                $priceText = isset($existingProduct['selling_price']) ? ", Price: Rs. " . number_format($existingProduct['selling_price'], 2) : "";
                $stockText = isset($existingProduct['quantity']) ? ", Stock: " . $existingProduct['quantity'] . " units" : "";
                $message = "Barcode '{$barcode}' already exists in the system. It is currently associated with: \"{$existingProduct['name']}\"{$categoryText}{$priceText}{$stockText}.";
                
                sendJSON([
                    'success' => false, 
                    'message' => $message,
                    'conflict_product' => [
                        'id' => (int)$existingProduct['id'],
                        'name' => $existingProduct['name'],
                        'barcode' => $existingProduct['barcode'],
                        'selling_price' => (float)$existingProduct['selling_price'],
                        'quantity' => (int)$existingProduct['quantity'],
                        'category_name' => $existingProduct['category_name'] ?? 'Uncategorized'
                    ]
                ], 400);
            }
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO products (name, barcode, cost_price, selling_price, quantity, category_id, description, show_in_pos, low_stock_threshold, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $barcode,
                $cost_price,
                $selling_price,
                $quantity,
                $category_id,
                $description,
                $show_in_pos,
                $low_stock_threshold,
                $status
            ]);
            
            $product_id = $db->lastInsertId();
            
            // Record initial stock movement if quantity is positive
            if ($quantity > 0) {
                $moveStmt = $db->prepare("
                    INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
                    VALUES (?, ?, 'manual_add', ?, 'Initial stock setup', ?)
                ");
                $moveStmt->execute([
                    $product_id,
                    $quantity,
                    $product_id,
                    $_SESSION['user_id']
                ]);
            }
            
            logAudit('product_create', 'products', $product_id, null, [
                'name' => $name,
                'barcode' => $barcode,
                'cost_price' => $cost_price,
                'selling_price' => $selling_price,
                'quantity' => $quantity,
                'category_id' => $category_id,
                'status' => $status
            ], 'Product added to inventory.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Product added successfully.', 'product_id' => $product_id]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => 'Failed to add product: ' . $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'update') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim(sanitize($_POST['name'])) : '';
        $barcode = isset($_POST['barcode']) ? trim(sanitize($_POST['barcode'])) : '';
        $cost_price = isset($_POST['cost_price']) ? (float)$_POST['cost_price'] : 0.00;
        $selling_price = isset($_POST['selling_price']) ? (float)$_POST['selling_price'] : 0.00;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
        if ($category_id <= 0) {
            $category_id = null;
        }
        $description = isset($_POST['description']) ? trim(sanitize($_POST['description'])) : '';
        $show_in_pos = isset($_POST['show_in_pos']) ? 1 : 0;
        $low_stock_threshold = isset($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : 10;
        if ($low_stock_threshold <= 0) {
            $low_stock_threshold = 10;
        }
        $status = ($show_in_pos === 1) ? 'active' : 'draft';
        $adjustment_reason = isset($_POST['adjustment_reason']) ? trim(sanitize($_POST['adjustment_reason'])) : '';
        
        // Fallback if reason_choice / other_reason_text sent directly
        if (empty($adjustment_reason) && isset($_POST['reason_choice'])) {
            $choice = trim(sanitize($_POST['reason_choice']));
            $otherText = isset($_POST['other_reason_text']) ? trim(sanitize($_POST['other_reason_text'])) : '';
            if ($choice === 'Other') {
                $adjustment_reason = !empty($otherText) ? "Other: {$otherText}" : 'Other';
            } else if (!empty($choice)) {
                $adjustment_reason = $choice;
            }
        }
        
        if ($id <= 0 || empty($name) || empty($barcode)) {
            sendJSON(['success' => false, 'message' => 'Invalid product parameters.'], 400);
        }
        
        // Check barcode uniqueness for other products and fetch conflicting product details
        $chk = $db->prepare("
            SELECT p.id, p.name, p.barcode, p.selling_price, p.quantity, c.name AS category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.barcode = ? AND p.id != ? 
            LIMIT 1
        ");
        $chk->execute([$barcode, $id]);
        $existingProduct = $chk->fetch();
        if ($existingProduct) {
            $categoryText = !empty($existingProduct['category_name']) ? " (Category: {$existingProduct['category_name']})" : "";
            $priceText = isset($existingProduct['selling_price']) ? ", Price: Rs. " . number_format($existingProduct['selling_price'], 2) : "";
            $stockText = isset($existingProduct['quantity']) ? ", Stock: " . $existingProduct['quantity'] . " units" : "";
            $message = "Barcode '{$barcode}' already assigned to another product: \"{$existingProduct['name']}\"{$categoryText}{$priceText}{$stockText}.";
            
            sendJSON([
                'success' => false, 
                'message' => $message,
                'conflict_product' => [
                    'id' => (int)$existingProduct['id'],
                    'name' => $existingProduct['name'],
                    'barcode' => $existingProduct['barcode'],
                    'selling_price' => (float)$existingProduct['selling_price'],
                    'quantity' => (int)$existingProduct['quantity'],
                    'category_name' => $existingProduct['category_name'] ?? 'Uncategorized'
                ]
            ], 400);
        }
        
        // Fetch current product state
        $currStmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $currStmt->execute([$id]);
        $current = $currStmt->fetch();
        
        if (!$current) {
            sendJSON(['success' => false, 'message' => 'Product not found.'], 404);
        }
        
        $qtyDifference = $quantity - $current['quantity'];
        
        // Enforce adjustment reason if stock quantity changed
        if ($qtyDifference != 0 && empty($adjustment_reason)) {
            sendJSON(['success' => false, 'message' => 'Reason for stock adjustment is required (Damage, New Stock, or Other).'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // Update product fields
            $updateStmt = $db->prepare("
                UPDATE products 
                SET name = ?, barcode = ?, cost_price = ?, selling_price = ?, quantity = ?, category_id = ?, description = ?, show_in_pos = ?, low_stock_threshold = ?, status = ?
                WHERE id = ?
            ");
            $updateStmt->execute([
                $name,
                $barcode,
                $cost_price,
                $selling_price,
                $quantity,
                $category_id,
                $description,
                $show_in_pos,
                $low_stock_threshold,
                $status,
                $id
            ]);
            
            // Record stock movement if quantity changed
            if ($qtyDifference != 0) {
                $mType = ($qtyDifference > 0) ? 'manual_add' : 'manual_remove';
                $moveStmt = $db->prepare("
                    INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $moveStmt->execute([
                    $id,
                    $qtyDifference,
                    $mType,
                    $id,
                    $adjustment_reason,
                    $_SESSION['user_id']
                ]);
            }
            
            logAudit('product_update', 'products', $id, $current, [
                'name' => $name,
                'barcode' => $barcode,
                'cost_price' => $cost_price,
                'selling_price' => $selling_price,
                'quantity' => $quantity,
                'category_id' => $category_id,
                'status' => $status,
                'adjustment_reason' => $adjustment_reason
            ], 'Product information updated.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Product updated successfully.']);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => 'Failed to update product: ' . $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'add_category') {
        $name = isset($_POST['name']) ? trim(sanitize($_POST['name'])) : '';
        
        if (empty($name)) {
            sendJSON(['success' => false, 'message' => 'Category name is required.'], 400);
        }
        
        // Enforce normalization/uniqueness
        $chk = $db->prepare("SELECT id FROM categories WHERE name = ?");
        $chk->execute([$name]);
        $existing = $chk->fetch();
        
        if ($existing) {
            sendJSON(['success' => false, 'message' => 'Category already exists.', 'category_id' => $existing['id']]);
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $category_id = $db->lastInsertId();
            
            logAudit('category_create', 'categories', $category_id, null, ['name' => $name], 'Category created.');
            
            sendJSON(['success' => true, 'message' => 'Category created successfully.', 'category_id' => $category_id, 'name' => $name]);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Failed to create category.'], 500);
        }
    }
    
    else if ($action === 'update_category') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim(sanitize($_POST['name'])) : '';
        $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
        
        if ($id <= 0 || empty($name)) {
            sendJSON(['success' => false, 'message' => 'Invalid category parameters.'], 400);
        }
        
        // Check uniqueness for other categories
        $chk = $db->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $chk->execute([$name, $id]);
        if ($chk->fetchColumn() > 0) {
            sendJSON(['success' => false, 'message' => 'Another category with this name already exists.'], 400);
        }
        
        $currStmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $currStmt->execute([$id]);
        $current = $currStmt->fetch();
        
        try {
            $stmt = $db->prepare("UPDATE categories SET name = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $status, $id]);
            
            logAudit('category_update', 'categories', $id, $current, ['name' => $name, 'status' => $status], 'Category details updated.');
            
            sendJSON(['success' => true, 'message' => 'Category updated successfully.']);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Failed to update category.'], 500);
        }
    }
}
