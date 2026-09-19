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
    if ($action === 'list_held') {
        requirePermission('CREATE_SALE');
        
        $stmt = $db->prepare("
            SELECT hc.*, COUNT(hci.id) as item_count, COALESCE(SUM(hci.quantity * p.selling_price), 0) as total_value
            FROM held_carts hc
            LEFT JOIN held_cart_items hci ON hc.id = hci.held_cart_id
            LEFT JOIN products p ON hci.product_id = p.id
            GROUP BY hc.id
            ORDER BY hc.id DESC
        ");
        $stmt->execute();
        $held = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'held' => $held]);
    }
    
    else if ($action === 'resume') {
        requirePermission('CREATE_SALE');
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid held cart ID.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // Get items
            $stmt = $db->prepare("
                SELECT hci.product_id, hci.quantity, p.name, p.barcode, p.selling_price, p.cost_price, p.quantity as stock_qty
                FROM held_cart_items hci
                JOIN products p ON hci.product_id = p.id
                WHERE hci.held_cart_id = ?
            ");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();
            
            // Delete held cart
            $delCart = $db->prepare("DELETE FROM held_carts WHERE id = ?");
            $delCart->execute([$id]);
            
            logAudit('resume_cart', 'held_carts', $id, null, null, 'Suspended cart resumed.');
            
            $db->commit();
            sendJSON(['success' => true, 'items' => $items]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => 'Failed to resume cart: ' . $e->getMessage()], 500);
        }
    }
} 

else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'hold') {
        // Staff with HOLD_BILL or CREATE_SALE can hold bills
        if (!hasPermission('HOLD_BILL') && !hasPermission('CREATE_SALE')) {
            sendJSON(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        
        $items = isset($_POST['items']) ? $_POST['items'] : []; // Array of {product_id, quantity}
        
        if (empty($items)) {
            sendJSON(['success' => false, 'message' => 'Cannot hold an empty cart.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // Use atomic database auto-increment ID to prevent duplicate key conflicts on deleted/resumed rows
            $tempHoldNumber = 'TEMP-' . uniqid() . '-' . mt_rand(1000, 9999);
            $insCart = $db->prepare("INSERT INTO held_carts (hold_number, cashier_id) VALUES (?, ?)");
            $insCart->execute([$tempHoldNumber, $_SESSION['user_id']]);
            $holdId = $db->lastInsertId();
            
            $holdNumber = 'H' . str_pad($holdId, 4, '0', STR_PAD_LEFT);
            $updateCart = $db->prepare("UPDATE held_carts SET hold_number = ? WHERE id = ?");
            $updateCart->execute([$holdNumber, $holdId]);
            
            $insItem = $db->prepare("INSERT INTO held_cart_items (held_cart_id, product_id, quantity) VALUES (?, ?, ?)");
            foreach ($items as $item) {
                $pid = (int)$item['product_id'];
                $qty = (int)$item['quantity'];
                $insItem->execute([$holdId, $pid, $qty]);
            }
            
            logAudit('hold_cart', 'held_carts', $holdId, null, ['hold_number' => $holdNumber], 'Cart suspended.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Cart held successfully.', 'hold_number' => $holdNumber]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => 'Failed to hold cart: ' . $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'cancel_held') {
        // Staff with HOLD_BILL or CREATE_SALE can cancel/delete held bills
        if (!hasPermission('HOLD_BILL') && !hasPermission('CREATE_SALE')) {
            sendJSON(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid held cart ID.'], 400);
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM held_carts WHERE id = ?");
            $stmt->execute([$id]);
            
            logAudit('cancel_held_cart', 'held_carts', $id, null, null, 'Suspended cart cancelled.');
            
            sendJSON(['success' => true, 'message' => 'Held cart cancelled successfully.']);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Failed to cancel held cart.'], 500);
        }
    }
    
    else if ($action === 'quick_save_customer') {
        requirePermission('CREATE_SALE');
        
        $name = trim(sanitize($_POST['name'] ?? ''));
        $phone = trim(sanitize($_POST['phone'] ?? ''));
        $type = sanitize($_POST['customer_type'] ?? 'general');
        $address = trim(sanitize($_POST['address'] ?? ''));
        $creditLimit = (float)($_POST['credit_limit'] ?? 0);
        $notes = trim(sanitize($_POST['notes'] ?? ''));
        
        if (empty($name)) {
            sendJSON(['success' => false, 'message' => 'Customer or shop name is required.'], 400);
        }
        
        if (!in_array($type, ['store', 'neighbor', 'friend', 'general'])) {
            $type = 'general';
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO customers (name, phone, customer_type, address, credit_limit, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$name, $phone, $type, $address, $creditLimit, $notes]);
            $newId = (int)$db->lastInsertId();
            
            logAudit('create_customer', 'customers', $newId, null, ['name' => $name, 'type' => $type], 'Quick customer created via POS checkout.');
            
            sendJSON([
                'success' => true,
                'message' => "Khata account for '$name' created successfully.",
                'customer' => [
                    'id' => $newId,
                    'name' => $name,
                    'phone' => $phone,
                    'customer_type' => $type,
                    'address' => $address,
                    'credit_limit' => $creditLimit,
                    'current_balance' => 0.00
                ]
            ]);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'checkout') {
        requirePermission('CREATE_SALE');
        
        $items = isset($_POST['items']) ? $_POST['items'] : []; // Array of {product_id, quantity, unit_price, discount_override}
        $discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0.00;
        $paid = isset($_POST['paid']) ? (float)$_POST['paid'] : 0.00;
        $payment_method = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : 'cash';
        $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        $khataDownPayment = isset($_POST['khata_down_payment']) ? (float)$_POST['khata_down_payment'] : 0.00;
        $notes = trim(sanitize($_POST['notes'] ?? ''));
        
        if (empty($items)) {
            sendJSON(['success' => false, 'message' => 'Cart is empty.'], 400);
        }
        
        if (!in_array($payment_method, ['cash', 'card', 'bank', 'khata', 'other'])) {
            sendJSON(['success' => false, 'message' => 'Invalid payment method.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // 1. If Khata payment, validate customer and credit limits
            $customer = null;
            $prevBalance = 0.00;
            if ($payment_method === 'khata') {
                if ($customerId <= 0) {
                    throw new Exception("Please select a valid Customer Khata account to charge this credit sale.");
                }
                
                $stmtCust = $db->prepare("
                    SELECT c.*,
                        (
                            COALESCE((SELECT SUM(b.total_amount) FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active'), 0) -
                            COALESCE((SELECT SUM(p.amount) FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active'), 0)
                        ) AS current_balance
                    FROM customers c
                    WHERE c.id = ? AND c.status = 'active'
                ");
                $stmtCust->execute([$customerId]);
                $customer = $stmtCust->fetch();
                
                if (!$customer) {
                    throw new Exception("Customer Khata account not found or is currently inactive.");
                }
                
                $prevBalance = (float)$customer['current_balance'];
            }
            
            $subtotal = 0.00;
            $sale_items_data = [];
            
            // 2. Loop and validate stock + custom special pricing
            foreach ($items as $cartItem) {
                $pid = (int)$cartItem['product_id'];
                $reqQty = (int)$cartItem['quantity'];
                
                if ($reqQty <= 0) {
                    throw new Exception("Invalid item quantity.");
                }
                
                // Concurrency safe: Fetch product details and lock row
                $stmt = $db->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
                $stmt->execute([$pid]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    throw new Exception("Product ID '$pid' not found.");
                }
                
                if ($product['status'] !== 'active') {
                    throw new Exception("Product '{$product['name']}' is inactive and cannot be sold.");
                }
                
                // Validate stock
                if ($product['quantity'] < $reqQty) {
                    throw new Exception("Insufficient stock for '{$product['name']}'. Available: {$product['quantity']}, Requested: $reqQty");
                }
                
                // Support Special / Custom Unit Price (e.g. for wholesale/friends/neighbor stores)
                $unitPrice = (isset($cartItem['unit_price']) && (float)$cartItem['unit_price'] >= 0) 
                    ? (float)$cartItem['unit_price'] 
                    : (float)$product['selling_price'];
                    
                $itemDiscount = isset($cartItem['discount_override']) ? (float)$cartItem['discount_override'] : 0.00;
                $netAmount = ($reqQty * $unitPrice) - $itemDiscount;
                
                if ($netAmount < 0) {
                    throw new Exception("Discount on '{$product['name']}' cannot exceed its total selling value.");
                }
                
                $subtotal += ($reqQty * $unitPrice);
                
                $sale_items_data[] = [
                    'product_id' => $pid,
                    'product_name' => $product['name'],
                    'quantity' => $reqQty,
                    'unit_price' => $unitPrice,
                    'cost_price_snapshot' => (float)$product['cost_price'], // Crucial for COGS and P&L
                    'discount' => $itemDiscount,
                    'net_amount' => $netAmount,
                    'new_stock' => $product['quantity'] - $reqQty
                ];
            }
            
            // 3. Calculate final totals
            $total = max(0.00, $subtotal - $discount);
            
            // Khata Credit Limit and Down Payment Check
            $netChargeToKhata = $total;
            $paidForSale = $paid;
            $change = 0.00;
            
            if ($payment_method === 'khata') {
                $khataDownPayment = max(0.00, min($khataDownPayment, $total));
                $netChargeToKhata = max(0.00, $total - $khataDownPayment);
                $expectedNewBalance = $prevBalance + $netChargeToKhata;
                $creditLimit = (float)$customer['credit_limit'];
                
                if ($creditLimit > 0 && $expectedNewBalance > $creditLimit) {
                    throw new Exception("Transaction exceeds Credit Limit of Rs. " . number_format($creditLimit, 2) . ". Current Balance: Rs. " . number_format($prevBalance, 2) . ", After Bill: Rs. " . number_format($expectedNewBalance, 2));
                }
                
                // Down payment cash tendered calculation
                $paidForSale = $khataDownPayment;
                $cashTendered = isset($_POST['cash_tendered']) ? (float)$_POST['cash_tendered'] : $khataDownPayment;
                $change = ($cashTendered > $khataDownPayment) ? ($cashTendered - $khataDownPayment) : 0.00;
            } else if ($payment_method === 'cash') {
                // Cash tendered is optional: if empty or zero, default to exact cash payment
                if ($paid <= 0) {
                    $paid = $total;
                }
                if ($paid < $total) {
                    throw new Exception("Paid amount (Rs. $paid) is less than total bill (Rs. $total).");
                }
                $paidForSale = $paid;
                $change = ($paid - $total);
            }
            
            // 4. Generate Master Bill Number: MELA-YYYYMMDD-XXXX
            $dateStr = date('Ymd');
            $stmt = $db->prepare("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURDATE()");
            $stmt->execute();
            $count = (int)$stmt->fetchColumn();
            $billNumber = 'MELA-' . $dateStr . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            
            // 5. Insert Sale Header
            $insSale = $db->prepare("
                INSERT INTO sales (bill_number, subtotal, discount, total, paid, returned_change, cashier_id, customer_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')
            ");
            $insSale->execute([
                $billNumber,
                $subtotal,
                $discount,
                $total,
                $paidForSale,
                $change,
                $_SESSION['user_id'],
                ($payment_method === 'khata') ? $customerId : null
            ]);
            $saleId = (int)$db->lastInsertId();
            
            // 6. Record Payments Entry
            if ($payment_method === 'khata') {
                $insPay = $db->prepare("INSERT INTO payments (sale_id, payment_method, amount) VALUES (?, ?, ?)");
                if ($khataDownPayment > 0) {
                    $insPay->execute([$saleId, 'cash', $khataDownPayment]);
                }
                if ($netChargeToKhata > 0) {
                    $insPay->execute([$saleId, 'khata', $netChargeToKhata]);
                }
            } else {
                $insPay = $db->prepare("INSERT INTO payments (sale_id, payment_method, amount) VALUES (?, ?, ?)");
                $insPay->execute([$saleId, $payment_method, $total]);
            }
            
            // 7. Allocate overall bill discount pro-rata across items
            $overallDiscountLeft = $discount;
            $itemsCount = count($sale_items_data);
            
            $insItem = $db->prepare("
                INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, cost_price_snapshot, discount, net_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $updateStock = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $insMove = $db->prepare("
                INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
                VALUES (?, ?, 'sale', ?, ?, ?)
            ");
            
            for ($i = 0; $i < $itemsCount; $i++) {
                $item = &$sale_items_data[$i];
                
                $allocatedBillDiscount = 0.00;
                if ($subtotal > 0 && $discount > 0) {
                    if ($i === $itemsCount - 1) {
                        $allocatedBillDiscount = $overallDiscountLeft;
                    } else {
                        $share = (($item['quantity'] * $item['unit_price']) / $subtotal);
                        $allocatedBillDiscount = round($discount * $share, 2);
                        $overallDiscountLeft -= $allocatedBillDiscount;
                    }
                }
                
                $totalItemDiscount = $item['discount'] + $allocatedBillDiscount;
                $finalNetAmount = ($item['quantity'] * $item['unit_price']) - $totalItemDiscount;
                $item['final_net_amount'] = $finalNetAmount;
                
                $insItem->execute([
                    $saleId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['cost_price_snapshot'],
                    $totalItemDiscount,
                    $finalNetAmount
                ]);
                
                // Deduct stock in products table
                $updateStock->execute([$item['new_stock'], $item['product_id']]);
                
                // Record stock movement (negative quantity)
                $insMove->execute([
                    $item['product_id'],
                    -$item['quantity'],
                    $saleId,
                    "Sold under POS Bill $billNumber" . ($payment_method === 'khata' ? " (Khata: {$customer['name']})" : ""),
                    $_SESSION['user_id']
                ]);
            }
            unset($item);
            
            // 8. If Khata, record in customer_khata_bills and customer_khata_bill_items
            $khataBillId = null;
            if ($payment_method === 'khata') {
                $billDate = date('Y-m-d');
                $billNotes = !empty($notes) ? $notes : "POS Credit Sale #$billNumber";
                
                $insKhataBill = $db->prepare("
                    INSERT INTO customer_khata_bills (bill_number, sale_id, customer_id, bill_date, total_amount, notes, created_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                $insKhataBill->execute([
                    $billNumber,
                    $saleId,
                    $customerId,
                    $billDate,
                    $total,
                    $billNotes,
                    $_SESSION['user_id']
                ]);
                $khataBillId = (int)$db->lastInsertId();
                
                // Link khata_bill_id back to sales
                $db->prepare("UPDATE sales SET khata_bill_id = ? WHERE id = ?")->execute([$khataBillId, $saleId]);
                
                // Copy item details to customer_khata_bill_items
                $insKhataItem = $db->prepare("
                    INSERT INTO customer_khata_bill_items (bill_id, product_id, item_name, quantity, unit_price, total_price, stock_deducted)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                foreach ($sale_items_data as $sItem) {
                    $insKhataItem->execute([
                        $khataBillId,
                        $sItem['product_id'],
                        $sItem['product_name'],
                        $sItem['quantity'],
                        $sItem['unit_price'],
                        $sItem['final_net_amount']
                    ]);
                }
                
                // If partial down payment was made in cash, record it as a recovery payment in Khata
                if ($khataDownPayment > 0) {
                    $receiptNumber = "REC-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -4));
                    $insKhataPay = $db->prepare("
                        INSERT INTO customer_khata_payments (receipt_number, customer_id, payment_date, amount, payment_method, notes, created_by, status)
                        VALUES (?, ?, ?, ?, 'cash', ?, ?, 'active')
                    ");
                    $insKhataPay->execute([
                        $receiptNumber,
                        $customerId,
                        $billDate,
                        $khataDownPayment,
                        "Counter Cash Down Payment for POS Bill $billNumber",
                        $_SESSION['user_id']
                    ]);
                }
            }
            
            logAudit('create_sale', 'sales', $saleId, null, [
                'bill_number' => $billNumber,
                'total' => $total,
                'paid' => $paidForSale,
                'payment_method' => $payment_method,
                'customer_id' => $customerId
            ], 'POS Checkout Completed.');
            
            $db->commit();
            
            sendJSON([
                'success' => true,
                'message' => 'Checkout completed successfully.',
                'bill_number' => $billNumber,
                'sale_id' => $saleId,
                'is_khata' => ($payment_method === 'khata'),
                'khata' => ($payment_method === 'khata') ? [
                    'customer_id' => $customerId,
                    'customer_name' => $customer['name'],
                    'customer_phone' => $customer['phone'] ?? '',
                    'customer_type' => $customer['customer_type'],
                    'previous_balance' => $prevBalance,
                    'bill_total' => $total,
                    'down_payment' => $khataDownPayment,
                    'net_charge' => $netChargeToKhata,
                    'new_balance' => ($prevBalance + $netChargeToKhata)
                ] : null
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
