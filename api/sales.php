<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

// Enforce login
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';
$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        requirePermission('VIEW_SALES');
        
        $search = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';
        $date_filter = isset($_GET['date_filter']) ? sanitize($_GET['date_filter']) : 'all';
        $start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
        $cashier_id = isset($_GET['cashier_id']) ? (int)$_GET['cashier_id'] : 0;
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        
        $where = ["1=1"];
        $params = [];
        
        // Granular protection: cashiers can only view their own sales unless VIEW_OTHER_CASHIERS_SALES is enabled
        if (!isAdmin() && !hasPermission('VIEW_OTHER_CASHIERS_SALES')) {
            $where[] = "s.cashier_id = ?";
            $params[] = $_SESSION['user_id'];
        } else if ($cashier_id > 0) {
            $where[] = "s.cashier_id = ?";
            $params[] = $cashier_id;
        }
        
        if (!empty($search)) {
            $where[] = "(s.bill_number LIKE ?)";
            $params[] = "%$search%";
        }
        
        if (!empty($status)) {
            $where[] = "s.status = ?";
            $params[] = $status;
        }
        
        // Date range filtering
        if ($date_filter === 'today') {
            $where[] = "DATE(s.created_at) = CURDATE()";
        } else if ($date_filter === 'yesterday') {
            $where[] = "DATE(s.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        } else if ($date_filter === 'week') {
            $where[] = "YEARWEEK(s.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        } else if ($date_filter === 'month') {
            $where[] = "MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())";
        } else if ($date_filter === 'last_2_months') {
            $where[] = "DATE(s.created_at) >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND DATE(s.created_at) <= CURDATE()";
        } else if ($date_filter === 'custom' && !empty($start_date) && !empty($end_date)) {
            $where[] = "DATE(s.created_at) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        
        $whereClause = implode(" AND ", $where);
        
        $query = "
            SELECT s.*, u.username as cashier_name,
                   v.username as voided_by_name,
                   (SELECT COALESCE(SUM(r.total_refunded), 0) FROM refunds r WHERE r.original_sale_id = s.id) as total_refunded_amount
            FROM sales s
            JOIN users u ON s.cashier_id = u.id
            LEFT JOIN users v ON s.voided_by = v.id
            WHERE $whereClause
            ORDER BY s.id DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $sales = $stmt->fetchAll();
        
        // Populate items for each sale in one single indexed query
        $saleIds = array_column($sales, 'id');
        $itemsBySale = [];
        if (!empty($saleIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($saleIds), '?'));
            $itemsStmt = $db->prepare("
                SELECT si.id, si.sale_id, si.product_id, si.quantity, si.unit_price, si.discount, si.net_amount,
                       p.name as product_name, p.barcode,
                       COALESCE((
                           SELECT SUM(ri.quantity_refunded) 
                           FROM refund_items ri 
                           JOIN refunds r ON ri.refund_id = r.id 
                           WHERE ri.sale_item_id = si.id
                       ), 0) as quantity_refunded
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                WHERE si.sale_id IN ($inPlaceholders)
                ORDER BY si.id ASC
            ");
            $itemsStmt->execute($saleIds);
            $allItems = $itemsStmt->fetchAll();
            foreach ($allItems as $item) {
                $itemsBySale[$item['sale_id']][] = $item;
            }
        }
        
        foreach ($sales as &$sale) {
            $sale['items'] = $itemsBySale[$sale['id']] ?? [];
        }
        unset($sale);
        
        sendJSON(['success' => true, 'sales' => $sales]);
    }
    
    else if ($action === 'search_bills') {
        requirePermission('VIEW_SALES');
        
        $term = isset($_GET['term']) ? trim(sanitize($_GET['term'])) : '';
        $mode = isset($_GET['mode']) ? sanitize($_GET['mode']) : ''; // 'refund', 'exchange', 'void'
        
        if (empty($term)) {
            sendJSON(['success' => true, 'bills' => []]);
        }
        
        $where = ["(s.bill_number LIKE ? OR u.username LIKE ?)"];
        $params = ["%$term%", "%$term%"];
        
        if ($mode === 'void') {
            $where[] = "s.status NOT IN ('voided', 'fully_refunded')";
        } else if ($mode === 'refund' || $mode === 'exchange') {
            $where[] = "s.status NOT IN ('voided', 'fully_refunded')";
        }
        
        $whereClause = implode(" AND ", $where);
        
        $query = "
            SELECT s.id, s.bill_number, s.total, s.subtotal, s.discount, s.status, s.created_at, u.username as cashier_name
            FROM sales s
            JOIN users u ON s.cashier_id = u.id
            WHERE $whereClause
            ORDER BY s.id DESC
            LIMIT 15
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $bills = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'bills' => $bills]);
    }
    
    else if ($action === 'get') {
        requirePermission('VIEW_SALES');
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $bill_number = isset($_GET['bill_number']) ? trim(sanitize($_GET['bill_number'])) : '';
        
        if ($id <= 0 && empty($bill_number)) {
            sendJSON(['success' => false, 'message' => 'Invalid sale ID or bill number.'], 400);
        }
        
        // Fetch header
        if ($id > 0) {
            $stmt = $db->prepare("
                SELECT s.*, u.username as cashier_name, v.username as voided_by_name
                FROM sales s
                JOIN users u ON s.cashier_id = u.id
                LEFT JOIN users v ON s.voided_by = v.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
        } else {
            $stmt = $db->prepare("
                SELECT s.*, u.username as cashier_name, v.username as voided_by_name
                FROM sales s
                JOIN users u ON s.cashier_id = u.id
                LEFT JOIN users v ON s.voided_by = v.id
                WHERE s.bill_number = ?
            ");
            $stmt->execute([$bill_number]);
        }
        $sale = $stmt->fetch();
        
        if (!$sale) {
            sendJSON(['success' => false, 'message' => 'Invoice not found.'], 404);
        }
        $id = $sale['id'];
        
        // Fetch items and calculate remaining refundable qty per item (sold_qty - refunded_qty)
        $stmtItems = $db->prepare("
            SELECT si.*, p.name as product_name, p.barcode,
                   COALESCE((
                       SELECT SUM(ri.quantity_refunded) 
                       FROM refund_items ri 
                       JOIN refunds r ON ri.refund_id = r.id 
                       WHERE ri.sale_item_id = si.id
                   ), 0) as quantity_refunded
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ");
        $stmtItems->execute([$id]);
        $items = $stmtItems->fetchAll();
        
        // Fetch payments
        $stmtPay = $db->prepare("SELECT * FROM payments WHERE sale_id = ?");
        $stmtPay->execute([$id]);
        $payments = $stmtPay->fetchAll();
        
        sendJSON([
            'success' => true,
            'sale' => $sale,
            'items' => $items,
            'payments' => $payments
        ]);
    }
    
    else if ($action === 'list_refunds') {
        requirePermission('REFUND');
        
        $stmt = $db->prepare("
            SELECT r.*, s.bill_number, u.username as cashier_name 
            FROM refunds r
            JOIN sales s ON r.original_sale_id = s.id
            JOIN users u ON r.cashier_id = u.id
            ORDER BY r.id DESC
        ");
        $stmt->execute();
        $refunds = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'refunds' => $refunds]);
    }
    
    else if ($action === 'list_exchanges') {
        requirePermission('EXCHANGE');
        
        $stmt = $db->prepare("
            SELECT e.*, s.bill_number, u.username as cashier_name 
            FROM exchanges e
            JOIN sales s ON e.original_sale_id = s.id
            JOIN users u ON e.cashier_id = u.id
            ORDER BY e.id DESC
        ");
        $stmt->execute();
        $exchanges = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'exchanges' => $exchanges]);
    }
    
    else if ($action === 'get_exchange') {
        requirePermission('EXCHANGE');
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $voucher = isset($_GET['voucher']) ? sanitize($_GET['voucher']) : '';
        
        if ($id <= 0 && empty($voucher)) {
            sendJSON(['success' => false, 'message' => 'Exchange ID or Voucher is required.'], 400);
        }
        
        $where = ($id > 0) ? "e.id = ?" : "e.exchange_voucher = ?";
        $param = ($id > 0) ? $id : $voucher;
        
        $stmt = $db->prepare("
            SELECT e.*, 
                   s.bill_number, 
                   s.created_at as original_bill_date, 
                   s.subtotal as original_subtotal,
                   s.discount as original_discount,
                   s.total as original_total,
                   s.paid as original_paid,
                   u.username as cashier_name 
            FROM exchanges e
            JOIN sales s ON e.original_sale_id = s.id
            JOIN users u ON e.cashier_id = u.id
            WHERE $where
        ");
        $stmt->execute([$param]);
        $exchange = $stmt->fetch();
        
        if (!$exchange) {
            sendJSON(['success' => false, 'message' => 'Exchange record not found.'], 404);
        }

        // Fetch original bill items for full audit integrity
        $stmtOrig = $db->prepare("
            SELECT si.*, p.name as product_name, p.barcode
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ");
        $stmtOrig->execute([$exchange['original_sale_id']]);
        $original_items = $stmtOrig->fetchAll();
        
        // Fetch returned items
        $stmtRet = $db->prepare("
            SELECT eri.*, p.name as product_name, p.barcode
            FROM exchange_returned_items eri
            JOIN products p ON eri.product_id = p.id
            WHERE eri.exchange_id = ?
        ");
        $stmtRet->execute([$exchange['id']]);
        $returned_items = $stmtRet->fetchAll();
        
        // Fetch replaced items
        $stmtRep = $db->prepare("
            SELECT erp.*, p.name as product_name, p.barcode
            FROM exchange_replaced_items erp
            JOIN products p ON erp.product_id = p.id
            WHERE erp.exchange_id = ?
        ");
        $stmtRep->execute([$exchange['id']]);
        $replaced_items = $stmtRep->fetchAll();
        
        sendJSON([
            'success' => true,
            'exchange' => $exchange,
            'original_items' => $original_items,
            'returned_items' => $returned_items,
            'replaced_items' => $replaced_items
        ]);
    }
} 

else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'void') {
        if (!isAdmin()) {
            sendJSON(['success' => false, 'message' => 'Access denied: Only Administrators can void or delete sales bills.'], 403);
        }
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $reason = isset($_POST['reason']) ? trim(sanitize($_POST['reason'])) : '';
        if (empty($reason)) {
            $reason = 'No reason provided';
        }
        
        if ($id <= 0) {
            sendJSON(['success' => false, 'message' => 'Valid Invoice ID is required.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // Check status
            $stmt = $db->prepare("SELECT * FROM sales WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $sale = $stmt->fetch();
            
            if (!$sale) {
                throw new Exception("Invoice not found.");
            }
            
            if ($sale['status'] === 'voided') {
                throw new Exception("Invoice is already voided.");
            }
            
            if ($sale['status'] === 'fully_refunded') {
                throw new Exception("Cannot void a fully refunded invoice.");
            }
            
            // Fetch items to reverse stock
            $stmtItems = $db->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll();
            
            $updateStock = $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $insMove = $db->prepare("
                INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
                VALUES (?, ?, 'void_reversal', ?, ?, ?)
            ");
            
            foreach ($items as $item) {
                // Return stock
                $updateStock->execute([$item['quantity'], $item['product_id']]);
                
                // Record movement
                $insMove->execute([
                    $item['product_id'],
                    $item['quantity'],
                    $id,
                    "Voided Bill {$sale['bill_number']}",
                    $_SESSION['user_id']
                ]);
            }
            
            // Update sale status
            $updateSale = $db->prepare("
                UPDATE sales 
                SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateSale->execute([$reason, $_SESSION['user_id'], $id]);

            // Sync Khata Ledger if linked to customer credit account
            if (!empty($sale['khata_bill_id'])) {
                $db->prepare("UPDATE customer_khata_bills SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = CURRENT_TIMESTAMP WHERE id = ?")
                   ->execute(["Voided via Sales: $reason", $_SESSION['user_id'], $sale['khata_bill_id']]);
                
                // If there was a down payment recovery payment logged for this bill, void that payment too
                $db->prepare("UPDATE customer_khata_payments SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = CURRENT_TIMESTAMP WHERE customer_id = ? AND notes LIKE ? AND status = 'active'")
                   ->execute(["Voided via Sales: $reason", $_SESSION['user_id'], $sale['customer_id'], "%{$sale['bill_number']}%"]);
            }
            
            logAudit('void_sale', 'sales', $id, $sale, ['status' => 'voided', 'reason' => $reason], 'Bill voided.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Invoice voided successfully.']);
        } catch (Throwable $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'refund') {
        requirePermission('REFUND');
        
        $sale_id = isset($_POST['sale_id']) ? (int)$_POST['sale_id'] : 0;
        $refund_items = isset($_POST['items']) ? $_POST['items'] : []; // Array of {sale_item_id, qty_to_refund}
        $reason = isset($_POST['reason']) ? trim(sanitize($_POST['reason'])) : '';
        
        if ($sale_id <= 0 || empty($refund_items) || empty($reason)) {
            sendJSON(['success' => false, 'message' => 'Sale ID, items list, and refund reason are required.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // Verify sale header
            $stmt = $db->prepare("SELECT * FROM sales WHERE id = ? FOR UPDATE");
            $stmt->execute([$sale_id]);
            $sale = $stmt->fetch();
            
            if (!$sale) {
                throw new Exception("Original invoice not found.");
            }
            
            if ($sale['status'] === 'voided') {
                throw new Exception("Cannot refund a voided invoice.");
            }
            
            if ($sale['status'] === 'fully_refunded') {
                throw new Exception("Invoice is already fully refunded.");
            }
            
            $totalRefundedAmount = 0.00;
            $refund_entries = [];
            
            // Validate limits and calculate pro-rata refunded amounts
            foreach ($refund_items as $refundReq) {
                $saleItemId = (int)$refundReq['sale_item_id'];
                $qtyRefund = (int)$refundReq['qty_to_refund'];
                
                if ($qtyRefund <= 0) continue;
                
                // Get item detail
                $stmtItem = $db->prepare("SELECT * FROM sale_items WHERE id = ? AND sale_id = ?");
                $stmtItem->execute([$saleItemId, $sale_id]);
                $saleItem = $stmtItem->fetch();
                
                if (!$saleItem) {
                    throw new Exception("Invoice item matching ID '$saleItemId' not found.");
                }
                
                // Get previously refunded quantity
                $stmtPrev = $db->prepare("
                    SELECT COALESCE(SUM(ri.quantity_refunded), 0) 
                    FROM refund_items ri
                    JOIN refunds r ON ri.refund_id = r.id
                    WHERE ri.sale_item_id = ?
                ");
                $stmtPrev->execute([$saleItemId]);
                $prevRefunded = (int)$stmtPrev->fetchColumn();
                
                $remainingRefundable = $saleItem['quantity'] - $prevRefunded;
                
                if ($qtyRefund > $remainingRefundable) {
                    throw new Exception("Refund quantity ($qtyRefund) exceeds remaining refundable limit ($remainingRefundable) for product ID {$saleItem['product_id']}.");
                }
                
                // Pro-rata refund value = (net_amount / quantity) * quantity_refunded
                $singleItemRefundVal = $saleItem['net_amount'] / $saleItem['quantity'];
                $refundVal = round($singleItemRefundVal * $qtyRefund, 2);
                
                $totalRefundedAmount += $refundVal;
                
                $refund_entries[] = [
                    'sale_item_id' => $saleItemId,
                    'product_id' => $saleItem['product_id'],
                    'qty_refunded' => $qtyRefund,
                    'amount' => $refundVal
                ];
            }
            
            if (empty($refund_entries)) {
                throw new Exception("No valid items to refund.");
            }
            
            // Generate Refund Voucher: REF-YYYYMMDD-XXXX
            $dateStr = date('Ymd');
            $stmt = $db->query("SELECT COUNT(*) FROM refunds");
            $count = $stmt->fetchColumn();
            $voucherNum = 'REF-' . $dateStr . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            
            // Insert refund header
            $insRefund = $db->prepare("
                INSERT INTO refunds (refund_voucher, original_sale_id, cashier_id, total_refunded, reason)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insRefund->execute([
                $voucherNum,
                $sale_id,
                $_SESSION['user_id'],
                $totalRefundedAmount,
                $reason
            ]);
            $refundId = $db->lastInsertId();
            
            // Create items, restore stocks & log movements
            $insRefundItem = $db->prepare("
                INSERT INTO refund_items (refund_id, sale_item_id, product_id, quantity_refunded, refund_amount)
                VALUES (?, ?, ?, ?, ?)
            ");
            $updateStock = $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $insMove = $db->prepare("
                INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
                VALUES (?, ?, 'refund', ?, ?, ?)
            ");
            
            foreach ($refund_entries as $entry) {
                $insRefundItem->execute([
                    $refundId,
                    $entry['sale_item_id'],
                    $entry['product_id'],
                    $entry['qty_refunded'],
                    $entry['amount']
                ]);
                
                $updateStock->execute([$entry['qty_refunded'], $entry['product_id']]);
                
                $insMove->execute([
                    $entry['product_id'],
                    $entry['qty_refunded'],
                    $refundId,
                    "Refund Voucher $voucherNum",
                    $_SESSION['user_id']
                ]);
            }
            
            // Determine new sale status (Check if everything is fully returned)
            $stmtItemsCount = $db->prepare("SELECT SUM(quantity) FROM sale_items WHERE sale_id = ?");
            $stmtItemsCount->execute([$sale_id]);
            $totalSold = (int)$stmtItemsCount->fetchColumn();
            
            $stmtRefundsCount = $db->prepare("
                SELECT SUM(ri.quantity_refunded) 
                FROM refund_items ri
                JOIN refunds r ON ri.refund_id = r.id
                WHERE r.original_sale_id = ?
            ");
            $stmtRefundsCount->execute([$sale_id]);
            $totalRefunded = (int)$stmtRefundsCount->fetchColumn();
            
            $newStatus = ($totalRefunded >= $totalSold) ? 'fully_refunded' : 'partially_refunded';
            
            $updateSale = $db->prepare("UPDATE sales SET status = ? WHERE id = ?");
            $updateSale->execute([$newStatus, $sale_id]);
            
            logAudit('create_refund', 'refunds', $refundId, null, [
                'refund_voucher' => $voucherNum,
                'sale_id' => $sale_id,
                'total_refunded' => $totalRefundedAmount
            ], 'Refund voucher processed.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Refund processed successfully.', 'voucher' => $voucherNum]);
        } catch (Throwable $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'exchange') {
        requirePermission('EXCHANGE');
        
        $sale_id = isset($_POST['sale_id']) ? (int)$_POST['sale_id'] : 0;
        $returned_items = isset($_POST['returned_items']) ? $_POST['returned_items'] : []; // Array of {sale_item_id, qty_returned}
        $replaced_items = isset($_POST['replaced_items']) ? $_POST['replaced_items'] : []; // Array of {product_id, qty_replaced}
        $settlement_method = isset($_POST['settlement_method']) ? sanitize($_POST['settlement_method']) : 'cash';
        
        if ($sale_id <= 0 || empty($returned_items) || empty($replaced_items)) {
            sendJSON(['success' => false, 'message' => 'Exchange requires a valid Sale ID, returned products, and replacement products.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("SELECT * FROM sales WHERE id = ? FOR UPDATE");
            $stmt->execute([$sale_id]);
            $sale = $stmt->fetch();
            
            if (!$sale) {
                throw new Exception("Original invoice not found.");
            }
            
            if ($sale['status'] === 'voided') {
                throw new Exception("Cannot process exchange on voided bill.");
            }
            
            // 1. Calculate Returned Value credit
            $returnedValueCredit = 0.00;
            $return_entries = [];
            
            foreach ($returned_items as $retReq) {
                $saleItemId = (int)$retReq['sale_item_id'];
                $qtyReturn = (int)$retReq['qty_returned'];
                
                if ($qtyReturn <= 0) continue;
                
                $stmtItem = $db->prepare("SELECT * FROM sale_items WHERE id = ? AND sale_id = ?");
                $stmtItem->execute([$saleItemId, $sale_id]);
                $saleItem = $stmtItem->fetch();
                
                if (!$saleItem) {
                    throw new Exception("Exchange returned item not found on invoice.");
                }
                
                // Get remaining refundable limits
                $stmtPrevRefund = $db->prepare("
                    SELECT COALESCE(SUM(ri.quantity_refunded), 0) 
                    FROM refund_items ri
                    JOIN refunds r ON ri.refund_id = r.id
                    WHERE ri.sale_item_id = ?
                ");
                $stmtPrevRefund->execute([$saleItemId]);
                $prevRefunded = (int)$stmtPrevRefund->fetchColumn();
                
                $stmtPrevExch = $db->prepare("
                    SELECT COALESCE(SUM(eri.quantity), 0)
                    FROM exchange_returned_items eri
                    WHERE eri.sale_item_id = ?
                ");
                $stmtPrevExch->execute([$saleItemId]);
                $prevExchanged = (int)$stmtPrevExch->fetchColumn();
                
                $remainingRefundable = $saleItem['quantity'] - $prevRefunded - $prevExchanged;
                
                if ($qtyReturn > $remainingRefundable) {
                    throw new Exception("Return quantity ($qtyReturn) exceeds remaining exchangeable limit ($remainingRefundable).");
                }
                
                $singleValue = $saleItem['net_amount'] / $saleItem['quantity'];
                $creditVal = round($singleValue * $qtyReturn, 2);
                $returnedValueCredit += $creditVal;
                
                $return_entries[] = [
                    'sale_item_id' => $saleItemId,
                    'product_id' => $saleItem['product_id'],
                    'qty' => $qtyReturn,
                    'credit' => $creditVal
                ];
            }
            
            // 2. Calculate Replacement Charged value and validate stock
            $replacementValueCost = 0.00;
            $replace_entries = [];
            
            foreach ($replaced_items as $repReq) {
                $pid = (int)$repReq['product_id'];
                $qtyReplace = (int)$repReq['qty_replaced'];
                
                if ($qtyReplace <= 0) continue;
                
                $stmtProd = $db->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
                $stmtProd->execute([$pid]);
                $product = $stmtProd->fetch();
                
                if (!$product) {
                    throw new Exception("Replacement product not found.");
                }
                
                if ($product['status'] !== 'active') {
                    throw new Exception("Replacement item '{$product['name']}' is not active.");
                }
                
                if ($product['quantity'] < $qtyReplace) {
                    throw new Exception("Insufficient stock for replacement '{$product['name']}'. Available: {$product['quantity']}, Requested: $qtyReplace");
                }
                
                $chargedVal = round($product['selling_price'] * $qtyReplace, 2);
                $replacementValueCost += $chargedVal;
                
                $replace_entries[] = [
                    'product_id' => $pid,
                    'qty' => $qtyReplace,
                    'charged' => $chargedVal,
                    'cost_snapshot' => $product['cost_price'],
                    'new_stock' => $product['quantity'] - $qtyReplace
                ];
            }
            
            if (empty($return_entries) || empty($replace_entries)) {
                throw new Exception("Exchange requires at least one returned item and one replacement item.");
            }
            
            $netDifference = $replacementValueCost - $returnedValueCredit;
            
            // Create exchange voucher
            $dateStr = date('Ymd');
            $stmt = $db->query("SELECT COUNT(*) FROM exchanges");
            $count = $stmt->fetchColumn();
            $voucherNum = 'EXC-' . $dateStr . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            
            $insExchange = $db->prepare("
                INSERT INTO exchanges (exchange_voucher, original_sale_id, cashier_id, returned_value, replaced_value, net_difference, settlement_method)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insExchange->execute([
                $voucherNum,
                $sale_id,
                $_SESSION['user_id'],
                $returnedValueCredit,
                $replacementValueCost,
                $netDifference,
                $settlement_method
            ]);
            $exchangeId = $db->lastInsertId();
            
            // Add return items details
            $insRet = $db->prepare("
                INSERT INTO exchange_returned_items (exchange_id, sale_item_id, product_id, quantity, value_credited)
                VALUES (?, ?, ?, ?, ?)
            ");
            $updateStockUp = $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $insMoveIn = $db->prepare("
                INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
                VALUES (?, ?, 'exchange_in', ?, ?, ?)
            ");
            
            foreach ($return_entries as $entry) {
                $insRet->execute([
                    $exchangeId,
                    $entry['sale_item_id'],
                    $entry['product_id'],
                    $entry['qty'],
                    $entry['credit']
                ]);
                $updateStockUp->execute([$entry['qty'], $entry['product_id']]);
                $insMoveIn->execute([
                    $entry['product_id'],
                    $entry['qty'],
                    $exchangeId,
                    "Exchange returned under voucher $voucherNum",
                    $_SESSION['user_id']
                ]);
            }
            
            // Add replacement items details
            $insRep = $db->prepare("
                INSERT INTO exchange_replaced_items (exchange_id, product_id, quantity, value_charged, cost_price_snapshot)
                VALUES (?, ?, ?, ?, ?)
            ");
            $updateStockDown = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $insMoveOut = $db->prepare("
                INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
                VALUES (?, ?, 'exchange_out', ?, ?, ?)
            ");
            
            foreach ($replace_entries as $entry) {
                $insRep->execute([
                    $exchangeId,
                    $entry['product_id'],
                    $entry['qty'],
                    $entry['charged'],
                    $entry['cost_snapshot']
                ]);
                $updateStockDown->execute([$entry['new_stock'], $entry['product_id']]);
                $insMoveOut->execute([
                    $entry['product_id'],
                    -$entry['qty'],
                    $exchangeId,
                    "Exchange replaced under voucher $voucherNum",
                    $_SESSION['user_id']
                ]);
            }
            
            logAudit('create_exchange', 'exchanges', $exchangeId, null, [
                'exchange_voucher' => $voucherNum,
                'returned_value' => $returnedValueCredit,
                'replaced_value' => $replacementValueCost,
                'difference' => $netDifference
            ], 'Exchange transaction processed.');
            
            $db->commit();
            sendJSON([
                'success' => true,
                'message' => 'Exchange completed successfully.',
                'exchange_id' => $exchangeId,
                'voucher' => $voucherNum,
                'difference' => $netDifference
            ]);
        } catch (Throwable $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
