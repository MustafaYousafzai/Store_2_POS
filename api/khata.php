<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

// Enforce login
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized. Please login.'], 401);
}

// Permission check
if (!hasPermission('MANAGE_KHATA') && !hasPermission('CREATE_SALE') && !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Forbidden: You do not have permission to manage Khata.'], 403);
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : (isset($_POST['action']) ? sanitize($_POST['action']) : '');
$db = getDBConnection();
$userId = (int)($_SESSION['user_id'] ?? 1);

try {
    // -------------------------------------------------------------------------
    // 1. SUMMARY KPIS (Dukan Ne Lena Hai, Aaj Ka Udhar, Aaj Ki Vasooli)
    // -------------------------------------------------------------------------
    if ($action === 'summary_kpi') {
        $today = date('Y-m-d');

        // Total active customers
        $stmtCust = $db->query("SELECT COUNT(*) as total_cust FROM customers WHERE status = 'active'");
        $totalCust = (int)$stmtCust->fetchColumn();

        // Total credit given all-time (active bills)
        $stmtTotCredit = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM customer_khata_bills WHERE status = 'active'");
        $allCredit = (float)$stmtTotCredit->fetchColumn();

        // Total recovery all-time (active payments)
        $stmtTotPaid = $db->query("SELECT COALESCE(SUM(amount), 0) FROM customer_khata_payments WHERE status = 'active'");
        $allPaid = (float)$stmtTotPaid->fetchColumn();

        // Calculate true receivables (sum of all positive balances) and advances
        $stmtCustBals = $db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END), 0) AS total_receivables,
                COALESCE(SUM(CASE WHEN balance < 0 THEN -balance ELSE 0 END), 0) AS total_advances,
                COUNT(CASE WHEN balance > 0 THEN 1 END) AS debtors_count
            FROM (
                SELECT 
                    c.id,
                    (
                        COALESCE((SELECT SUM(b.total_amount) FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active'), 0) -
                        COALESCE((SELECT SUM(p.amount) FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active'), 0)
                    ) AS balance
                FROM customers c
                WHERE c.status = 'active'
            ) AS cust_bals
        ");
        $balStats = $stmtCustBals->fetch();
        $totalReceivables = (float)$balStats['total_receivables'];
        $totalAdvances = (float)$balStats['total_advances'];
        $debtorsCount = (int)$balStats['debtors_count'];

        // Today's Credit Sales
        $stmtTodayCredit = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM customer_khata_bills WHERE bill_date = ? AND status = 'active'");
        $stmtTodayCredit->execute([$today]);
        $todayCredit = (float)$stmtTodayCredit->fetchColumn();

        // Today's Recovery Payments
        $stmtTodayPaid = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM customer_khata_payments WHERE payment_date = ? AND status = 'active'");
        $stmtTodayPaid->execute([$today]);
        $todayPaid = (float)$stmtTodayPaid->fetchColumn();

        sendJSON([
            'success' => true,
            'total_receivables' => $totalReceivables,
            'today_credit' => $todayCredit,
            'today_recovery' => $todayPaid,
            'total_customers' => $totalCust,
            'debtors_count' => $debtorsCount,
            'all_credit' => $allCredit,
            'all_paid' => $allPaid,
            'today' => $today
        ]);
    }

    // -------------------------------------------------------------------------
    // 1b. LIST ACTIVE EMPLOYEES (For receiving staff dropdown)
    // -------------------------------------------------------------------------
    else if ($action === 'list_employees_for_select') {
        $stmtEmps = $db->query("SELECT id, name, designation FROM employees WHERE status = 'active' ORDER BY name ASC");
        $employees = $stmtEmps->fetchAll(PDO::FETCH_ASSOC);
        sendJSON(['success' => true, 'employees' => $employees]);
    }

    // -------------------------------------------------------------------------
    // 2. LIST CUSTOMERS (With live balance and last transaction date)
    // -------------------------------------------------------------------------
    else if ($action === 'list_customers') {
        $search = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';
        $type = isset($_GET['customer_type']) ? sanitize($_GET['customer_type']) : '';
        $filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all'; // 'all', 'pending', 'cleared'

        $where = ["c.status = 'active'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.address LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($type) && in_array($type, ['store', 'neighbor', 'friend', 'general'])) {
            $where[] = "c.customer_type = ?";
            $params[] = $type;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "
            SELECT 
                c.*,
                COALESCE((SELECT SUM(b.total_amount) FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active'), 0) AS total_credit,
                COALESCE((SELECT SUM(p.amount) FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active'), 0) AS total_paid,
                (
                    COALESCE((SELECT SUM(b.total_amount) FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active'), 0) -
                    COALESCE((SELECT SUM(p.amount) FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active'), 0)
                ) AS current_balance,
                (SELECT COUNT(*) FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active') AS total_bills_count,
                (SELECT COUNT(*) FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active') AS total_payments_count,
                (SELECT b.id FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active' ORDER BY b.bill_date DESC, b.id DESC LIMIT 1) AS last_bill_id,
                (SELECT b.bill_number FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active' ORDER BY b.bill_date DESC, b.id DESC LIMIT 1) AS last_bill_number,
                (SELECT b.bill_date FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active' ORDER BY b.bill_date DESC, b.id DESC LIMIT 1) AS last_bill_date,
                (SELECT b.total_amount FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active' ORDER BY b.bill_date DESC, b.id DESC LIMIT 1) AS last_bill_amount,
                (SELECT b.created_at FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active' ORDER BY b.bill_date DESC, b.id DESC LIMIT 1) AS last_bill_created_at,
                (SELECT p.id FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active' ORDER BY p.payment_date DESC, p.id DESC LIMIT 1) AS last_pay_id,
                (SELECT p.receipt_number FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active' ORDER BY p.payment_date DESC, p.id DESC LIMIT 1) AS last_pay_receipt,
                (SELECT p.payment_date FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active' ORDER BY p.payment_date DESC, p.id DESC LIMIT 1) AS last_pay_date,
                (SELECT p.amount FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active' ORDER BY p.payment_date DESC, p.id DESC LIMIT 1) AS last_pay_amount,
                (SELECT p.payment_method FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active' ORDER BY p.payment_date DESC, p.id DESC LIMIT 1) AS last_pay_method,
                (SELECT p.created_at FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active' ORDER BY p.payment_date DESC, p.id DESC LIMIT 1) AS last_pay_created_at,
                GREATEST(
                    COALESCE((SELECT MAX(b.created_at) FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active'), '2000-01-01'),
                    COALESCE((SELECT MAX(p.created_at) FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active'), '2000-01-01')
                ) AS last_activity
            FROM customers c
            WHERE $whereClause
            ORDER BY current_balance DESC, c.name ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Fetch last bill items in batch for quick overview
        $lastBillIds = array_filter(array_column($rows, 'last_bill_id'));
        $lastBillItemsMap = [];
        if (!empty($lastBillIds)) {
            $placeholders = implode(',', array_fill(0, count($lastBillIds), '?'));
            $stmtItems = $db->prepare("SELECT bill_id, item_name, quantity, unit_price, total_price FROM customer_khata_bill_items WHERE bill_id IN ($placeholders)");
            $stmtItems->execute(array_values($lastBillIds));
            $allItems = $stmtItems->fetchAll();
            foreach ($allItems as $it) {
                $lastBillItemsMap[$it['bill_id']][] = $it;
            }
        }

        $customers = [];
        foreach ($rows as $r) {
            $bal = (float)$r['current_balance'];
            
            // Apply filter
            if ($filter === 'pending' && $bal <= 0) continue;
            if ($filter === 'cleared' && $bal != 0) continue;

            $itemsForBill = $lastBillItemsMap[$r['last_bill_id']] ?? [];
            $summaryParts = [];
            foreach ($itemsForBill as $it) {
                $summaryParts[] = $it['item_name'] . ' (' . (float)$it['quantity'] . ')';
            }
            $lastBillSummary = implode(', ', array_slice($summaryParts, 0, 3));
            if (count($summaryParts) > 3) $lastBillSummary .= '...';

            $customers[] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'phone' => $r['phone'] ?? '',
                'customer_type' => $r['customer_type'],
                'address' => $r['address'] ?? '',
                'credit_limit' => (float)$r['credit_limit'],
                'notes' => $r['notes'] ?? '',
                'status' => $r['status'],
                'total_credit' => (float)$r['total_credit'],
                'total_paid' => (float)$r['total_paid'],
                'current_balance' => $bal,
                'total_bills_count' => (int)$r['total_bills_count'],
                'total_payments_count' => (int)$r['total_payments_count'],
                'last_bill' => $r['last_bill_id'] ? [
                    'id' => (int)$r['last_bill_id'],
                    'bill_number' => $r['last_bill_number'],
                    'bill_date' => $r['last_bill_date'],
                    'amount' => (float)$r['last_bill_amount'],
                    'created_at' => $r['last_bill_created_at'],
                    'items_summary' => $lastBillSummary,
                    'items' => $itemsForBill
                ] : null,
                'last_payment' => $r['last_pay_id'] ? [
                    'id' => (int)$r['last_pay_id'],
                    'receipt_number' => $r['last_pay_receipt'],
                    'payment_date' => $r['last_pay_date'],
                    'amount' => (float)$r['last_pay_amount'],
                    'method' => $r['last_pay_method'],
                    'created_at' => $r['last_pay_created_at']
                ] : null,
                'last_activity' => $r['last_activity'] !== '2000-01-01' ? $r['last_activity'] : null,
                'created_at' => $r['created_at']
            ];
        }

        sendJSON([
            'success' => true,
            'customers' => $customers,
            'count' => count($customers)
        ]);
    }

    // -------------------------------------------------------------------------
    // 3. GET SINGLE CUSTOMER PROFILE
    // -------------------------------------------------------------------------
    else if ($action === 'get_customer') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("
            SELECT 
                c.*,
                COALESCE((SELECT SUM(b.total_amount) FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active'), 0) AS total_credit,
                COALESCE((SELECT SUM(p.amount) FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active'), 0) AS total_paid,
                (
                    COALESCE((SELECT SUM(b.total_amount) FROM customer_khata_bills b WHERE b.customer_id = c.id AND b.status = 'active'), 0) -
                    COALESCE((SELECT SUM(p.amount) FROM customer_khata_payments p WHERE p.customer_id = c.id AND p.status = 'active'), 0)
                ) AS current_balance
            FROM customers c
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            sendJSON(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        $customer['id'] = (int)$customer['id'];
        $customer['total_credit'] = (float)$customer['total_credit'];
        $customer['total_paid'] = (float)$customer['total_paid'];
        $customer['current_balance'] = (float)$customer['current_balance'];

        sendJSON(['success' => true, 'customer' => $customer]);
    }

    // -------------------------------------------------------------------------
    // 4. SAVE CUSTOMER (ADD / UPDATE)
    // -------------------------------------------------------------------------
    else if ($action === 'save_customer') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim(sanitize($_POST['name'] ?? ''));
        $phone = trim(sanitize($_POST['phone'] ?? ''));
        $type = sanitize($_POST['customer_type'] ?? 'general');
        $address = trim(sanitize($_POST['address'] ?? ''));
        $creditLimit = (float)($_POST['credit_limit'] ?? 0);
        $notes = trim(sanitize($_POST['notes'] ?? ''));

        if (empty($name)) {
            sendJSON(['success' => false, 'message' => 'Customer name is required.'], 422);
        }

        if (!in_array($type, ['store', 'neighbor', 'friend', 'general'])) {
            $type = 'general';
        }

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE customers 
                SET name = ?, phone = ?, customer_type = ?, address = ?, credit_limit = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $type, $address, $creditLimit, $notes, $id]);
            sendJSON(['success' => true, 'message' => "Customer '$name' updated successfully.", 'customer_id' => $id]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO customers (name, phone, customer_type, address, credit_limit, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $phone, $type, $address, $creditLimit, $notes]);
            $newId = (int)$db->lastInsertId();
            sendJSON(['success' => true, 'message' => "New Khata account created for '$name'.", 'customer_id' => $newId]);
        }
    }

    // -------------------------------------------------------------------------
    // 5. SAVE CREDIT SALE (SAAMAN UDHAR DIYA - ITEMIZED BILL)
    // -------------------------------------------------------------------------
    else if ($action === 'save_credit_sale') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $billDate = sanitize($_POST['bill_date'] ?? date('Y-m-d'));
        $notes = trim(sanitize($_POST['notes'] ?? ''));
        $deductStock = isset($_POST['deduct_stock']) ? (bool)$_POST['deduct_stock'] : true;

        if ($customerId <= 0) {
            sendJSON(['success' => false, 'message' => 'Please select a valid customer.'], 422);
        }

        // Validate customer exists
        $chkCust = $db->prepare("SELECT id, name, phone FROM customers WHERE id = ? AND status = 'active'");
        $chkCust->execute([$customerId]);
        $cust = $chkCust->fetch();
        if (!$cust) {
            sendJSON(['success' => false, 'message' => 'Active customer account not found.'], 404);
        }

        // Parse items
        $itemsRaw = $_POST['items'] ?? null;
        if (is_string($itemsRaw)) {
            $items = json_decode($itemsRaw, true);
        } else if (is_array($itemsRaw)) {
            $items = $itemsRaw;
        } else {
            $items = [];
        }

        if (empty($items) || !is_array($items)) {
            sendJSON(['success' => false, 'message' => 'Please add at least one item to the credit bill.'], 422);
        }

        // Generate unique bill number
        $datePrefix = date('Ymd', strtotime($billDate));
        $randSuffix = strtoupper(substr(uniqid(), -4));
        $billNumber = "UDH-{$datePrefix}-{$randSuffix}";

        $db->beginTransaction();

        $totalBillAmount = 0;
        $processedItems = [];

        // 1. Insert master bill row
        $stmtBill = $db->prepare("
            INSERT INTO customer_khata_bills (bill_number, customer_id, bill_date, total_amount, notes, created_by)
            VALUES (?, ?, ?, 0, ?, ?)
        ");
        $stmtBill->execute([$billNumber, $customerId, $billDate, $notes, $userId]);
        $billId = (int)$db->lastInsertId();

        // Prepared statements for items & stock
        $stmtItem = $db->prepare("
            INSERT INTO customer_khata_bill_items (bill_id, product_id, item_name, quantity, unit_price, total_price, stock_deducted)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtDeduct = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $stmtStockMv = $db->prepare("
            INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
            VALUES (?, ?, 'khata_credit_sale', ?, ?, ?)
        ");

        foreach ($items as $idx => $it) {
            $itemName = trim(sanitize($it['item_name'] ?? ''));
            $productId = isset($it['product_id']) && (int)$it['product_id'] > 0 ? (int)$it['product_id'] : null;
            $qty = (float)($it['quantity'] ?? 1);
            $unitPrice = (float)($it['unit_price'] ?? 0);

            if (empty($itemName)) {
                continue; // Skip blank rows
            }
            if ($qty <= 0) $qty = 1;
            if ($unitPrice < 0) $unitPrice = 0;

            $rowTotal = round($qty * $unitPrice, 2);
            $totalBillAmount += $rowTotal;

            $stockDeductedFlag = 0;
            if ($deductStock && $productId !== null) {
                $stmtDeduct->execute([$qty, $productId]);
                $stmtStockMv->execute([
                    $productId, 
                    -$qty, 
                    $billId, 
                    "Credit sale to {$cust['name']} (Bill #$billNumber)", 
                    $userId
                ]);
                $stockDeductedFlag = 1;
            }

            $stmtItem->execute([
                $billId,
                $productId,
                $itemName,
                $qty,
                $unitPrice,
                $rowTotal,
                $stockDeductedFlag
            ]);

            $processedItems[] = [
                'item_name' => $itemName,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $rowTotal
            ];
        }

        if ($totalBillAmount <= 0 && empty($processedItems)) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => 'Total bill amount must be greater than zero.'], 422);
        }

        // 2. Update master bill total
        $stmtUpd = $db->prepare("UPDATE customer_khata_bills SET total_amount = ? WHERE id = ?");
        $stmtUpd->execute([$totalBillAmount, $billId]);

        $db->commit();

        // Fetch updated live customer balance
        $stmtBal = $db->prepare("
            SELECT 
                COALESCE((SELECT SUM(b.total_amount) FROM customer_khata_bills b WHERE b.customer_id = ? AND b.status = 'active'), 0) -
                COALESCE((SELECT SUM(p.amount) FROM customer_khata_payments p WHERE p.customer_id = ? AND p.status = 'active'), 0) AS new_balance
        ");
        $stmtBal->execute([$customerId, $customerId]);
        $newBalance = (float)$stmtBal->fetchColumn();

        sendJSON([
            'success' => true,
            'message' => "Credit bill #$billNumber (Rs. " . number_format($totalBillAmount, 2) . ") recorded for {$cust['name']}.",
            'bill_id' => $billId,
            'bill_number' => $billNumber,
            'bill_date' => $billDate,
            'customer_name' => $cust['name'],
            'customer_phone' => $cust['phone'],
            'total_amount' => $totalBillAmount,
            'new_balance' => $newBalance,
            'items' => $processedItems,
            'notes' => $notes
        ]);
    }

    // -------------------------------------------------------------------------
    // 6. SAVE VASOOLI / PAYMENT RECOVERY
    // -------------------------------------------------------------------------
    else if ($action === 'save_payment') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $paymentDate = sanitize($_POST['payment_date'] ?? date('Y-m-d'));
        $amount = (float)($_POST['amount'] ?? 0);
        $method = sanitize($_POST['payment_method'] ?? 'cash');
        $notes = trim(sanitize($_POST['notes'] ?? ''));
        $receivedByEmpId = isset($_POST['received_by_employee_id']) && (int)$_POST['received_by_employee_id'] > 0 ? (int)$_POST['received_by_employee_id'] : null;

        $receivedByEmpName = null;
        if ($receivedByEmpId) {
            $chkEmp = $db->prepare("SELECT name FROM employees WHERE id = ?");
            $chkEmp->execute([$receivedByEmpId]);
            $receivedByEmpName = $chkEmp->fetchColumn() ?: null;
        }

        if ($customerId <= 0) {
            sendJSON(['success' => false, 'message' => 'Please select a valid customer.'], 422);
        }
        if ($amount <= 0) {
            sendJSON(['success' => false, 'message' => 'Please enter a valid payment amount greater than zero.'], 422);
        }
        if (!in_array($method, ['cash', 'bank', 'easypaisa_jazzcash'])) {
            $method = 'cash';
        }

        // Validate customer
        $chkCust = $db->prepare("SELECT id, name, phone FROM customers WHERE id = ? AND status = 'active'");
        $chkCust->execute([$customerId]);
        $cust = $chkCust->fetch();
        if (!$cust) {
            sendJSON(['success' => false, 'message' => 'Customer account not found.'], 404);
        }

        // Previous balance before payment
        $stmtPrev = $db->prepare("
            SELECT 
                COALESCE((SELECT SUM(b.total_amount) FROM customer_khata_bills b WHERE b.customer_id = ? AND b.status = 'active'), 0) -
                COALESCE((SELECT SUM(p.amount) FROM customer_khata_payments p WHERE p.customer_id = ? AND p.status = 'active'), 0) AS prev_balance
        ");
        $stmtPrev->execute([$customerId, $customerId]);
        $prevBalance = (float)$stmtPrev->fetchColumn();

        // Generate receipt number
        $datePrefix = date('Ymd', strtotime($paymentDate));
        $randSuffix = strtoupper(substr(uniqid(), -4));
        $receiptNumber = "REC-{$datePrefix}-{$randSuffix}";

        $db->beginTransaction();

        $stmtPay = $db->prepare("
            INSERT INTO customer_khata_payments (receipt_number, customer_id, payment_date, amount, payment_method, notes, created_by, received_by_employee_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtPay->execute([$receiptNumber, $customerId, $paymentDate, $amount, $method, $notes, $userId, $receivedByEmpId]);
        $paymentId = (int)$db->lastInsertId();

        $db->commit();

        $newBalance = $prevBalance - $amount;

        sendJSON([
            'success' => true,
            'message' => "Vasooli payment of Rs. " . number_format($amount, 2) . " received from {$cust['name']}.",
            'payment_id' => $paymentId,
            'receipt_number' => $receiptNumber,
            'payment_date' => $paymentDate,
            'customer_name' => $cust['name'],
            'customer_phone' => $cust['phone'],
            'amount_paid' => $amount,
            'previous_balance' => $prevBalance,
            'new_balance' => $newBalance,
            'payment_method' => $method,
            'received_by_employee_id' => $receivedByEmpId,
            'received_by_employee_name' => $receivedByEmpName,
            'notes' => $notes
        ]);
    }

    // -------------------------------------------------------------------------
    // 7. CUSTOMER PASSBOOK & FULL KHATA LEDGER
    // -------------------------------------------------------------------------
    else if ($action === 'customer_ledger') {
        $customerId = (int)($_GET['customer_id'] ?? 0);
        $period = sanitize($_GET['period'] ?? 'all'); // 'all', 'this_month', 'last_month', 'custom'
        $startDate = sanitize($_GET['start_date'] ?? '');
        $endDate = sanitize($_GET['end_date'] ?? '');

        if ($customerId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid customer ID.'], 422);
        }

        // Fetch customer profile
        $stmtCust = $db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmtCust->execute([$customerId]);
        $customer = $stmtCust->fetch();
        if (!$customer) {
            sendJSON(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        // Determine date filter
        if ($period === 'this_month') {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        } else if ($period === 'last_month') {
            $startDate = date('Y-m-01', strtotime('-1 month'));
            $endDate = date('Y-m-t', strtotime('-1 month'));
        }

        $hasDateFilter = (!empty($startDate) && !empty($endDate));

        // 1. Compute Opening Balance (if date filter applied)
        $openingBalance = 0;
        if ($hasDateFilter) {
            $stmtOpBills = $db->prepare("
                SELECT COALESCE(SUM(total_amount), 0) FROM customer_khata_bills 
                WHERE customer_id = ? AND bill_date < ? AND status = 'active'
            ");
            $stmtOpBills->execute([$customerId, $startDate]);
            $opBills = (float)$stmtOpBills->fetchColumn();

            $stmtOpPays = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM customer_khata_payments 
                WHERE customer_id = ? AND payment_date < ? AND status = 'active'
            ");
            $stmtOpPays->execute([$customerId, $startDate]);
            $opPays = (float)$stmtOpPays->fetchColumn();

            $openingBalance = $opBills - $opPays;
        }

        // 2. Fetch Bills in range
        $billSql = "
            SELECT 
                b.id,
                b.bill_number as ref_no,
                b.bill_date as tx_date,
                b.total_amount as debit,
                0 as credit,
                'bill' as tx_type,
                b.notes,
                b.status,
                u.username as created_by_name,
                b.created_at
            FROM customer_khata_bills b
            LEFT JOIN users u ON b.created_by = u.id
            WHERE b.customer_id = ? AND b.status = 'active'
        ";
        $billParams = [$customerId];
        if ($hasDateFilter) {
            $billSql .= " AND b.bill_date >= ? AND b.bill_date <= ?";
            $billParams[] = $startDate;
            $billParams[] = $endDate;
        }
        $billSql .= " ORDER BY b.bill_date DESC, b.created_at DESC, b.id DESC";
        $stmtBills = $db->prepare($billSql);
        $stmtBills->execute($billParams);
        $rawBills = $stmtBills->fetchAll();

        // Attach items to bills
        $stmtItems = $db->prepare("
            SELECT bi.id, bi.product_id, bi.item_name, bi.quantity, bi.unit_price, bi.total_price, p.barcode 
            FROM customer_khata_bill_items bi
            LEFT JOIN products p ON bi.product_id = p.id
            WHERE bi.bill_id = ?
        ");
        $stmtDp = $db->prepare("
            SELECT p.id, p.amount, p.receipt_number, p.received_by_employee_id, e.name as received_by_employee_name 
            FROM customer_khata_payments p 
            LEFT JOIN employees e ON p.received_by_employee_id = e.id
            WHERE p.customer_id = ? AND p.notes LIKE ? AND p.status = 'active' 
            LIMIT 1
        ");
        $linkedPaymentIds = [];

        foreach ($rawBills as &$b) {
            $stmtItems->execute([$b['id']]);
            $b['items'] = $stmtItems->fetchAll();
            $b['items_summary'] = count($b['items']) . " item(s): " . implode(", ", array_slice(array_column($b['items'], 'item_name'), 0, 3));
            if (count($b['items']) > 3) $b['items_summary'] .= "...";

            $stmtDp->execute([$customerId, "%{$b['ref_no']}%"]);
            $dpRow = $stmtDp->fetch();
            if ($dpRow) {
                $b['down_payment'] = (float)$dpRow['amount'];
                $b['down_payment_id'] = (int)$dpRow['id'];
                $b['down_payment_receipt'] = $dpRow['receipt_number'];
                $b['received_by_employee_id'] = $dpRow['received_by_employee_id'];
                $b['received_by_employee_name'] = $dpRow['received_by_employee_name'];
                $linkedPaymentIds[$dpRow['id']] = true;
            } else {
                $b['down_payment'] = 0;
                $b['down_payment_id'] = null;
                $b['down_payment_receipt'] = null;
                $b['received_by_employee_id'] = null;
                $b['received_by_employee_name'] = null;
            }

            $b['bill_amount'] = (float)$b['debit'];
            $b['paid_amount'] = (float)$b['down_payment'];
            $b['net_change'] = $b['bill_amount'] - $b['paid_amount'];
        }
        unset($b);

        // 3. Fetch Payments in range
        $paySql = "
            SELECT 
                p.id,
                p.receipt_number as ref_no,
                p.payment_date as tx_date,
                0 as debit,
                p.amount as credit,
                'payment' as tx_type,
                p.payment_method,
                p.notes,
                p.status,
                p.received_by_employee_id,
                e.name as received_by_employee_name,
                u.username as created_by_name,
                p.created_at
            FROM customer_khata_payments p
            LEFT JOIN users u ON p.created_by = u.id
            LEFT JOIN employees e ON p.received_by_employee_id = e.id
            WHERE p.customer_id = ? AND p.status = 'active'
        ";
        $payParams = [$customerId];
        if ($hasDateFilter) {
            $paySql .= " AND p.payment_date >= ? AND p.payment_date <= ?";
            $payParams[] = $startDate;
            $payParams[] = $endDate;
        }
        $paySql .= " ORDER BY p.payment_date DESC, p.created_at DESC, p.id DESC";
        $stmtPays = $db->prepare($paySql);
        $stmtPays->execute($payParams);
        $rawPays = $stmtPays->fetchAll();

        // Separate standalone payments from linked down payments
        $standalonePays = [];
        foreach ($rawPays as &$p) {
            $p['bill_amount'] = 0;
            $p['paid_amount'] = (float)$p['credit'];
            $p['net_change'] = -$p['paid_amount'];
            if (!isset($linkedPaymentIds[$p['id']])) {
                $standalonePays[] = $p;
            }
        }
        unset($p);

        // 4. Combine and sort chronologically (oldest to newest for calculating running balance)
        $allTx = array_merge($rawBills, $standalonePays);
        usort($allTx, function($a, $b) {
            $cmpDate = strcmp($a['tx_date'], $b['tx_date']);
            if ($cmpDate !== 0) return $cmpDate;
            return strcmp($a['created_at'], $b['created_at']);
        });

        // 5. Compute running balance
        $runningBal = $openingBalance;
        $totalPeriodDebit = 0;
        $totalPeriodCredit = 0;

        foreach ($allTx as &$tx) {
            if ($tx['tx_type'] === 'bill') {
                $totalPeriodDebit += $tx['bill_amount'];
                $totalPeriodCredit += $tx['paid_amount'];
            } else {
                $totalPeriodCredit += $tx['paid_amount'];
            }
            $runningBal += $tx['net_change'];
            $tx['balance_after'] = $runningBal;
        }
        unset($tx);

        // 6. Reverse allTx so the most recent / newest transaction is at the top of the table
        $allTx = array_reverse($allTx);

        // Lifetime totals for the customer
        $stmtLifeBills = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM customer_khata_bills WHERE customer_id = ? AND status = 'active'");
        $stmtLifeBills->execute([$customerId]);
        $lifetimeCredit = (float)$stmtLifeBills->fetchColumn();

        $stmtLifePays = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM customer_khata_payments WHERE customer_id = ? AND status = 'active'");
        $stmtLifePays->execute([$customerId]);
        $lifetimePaid = (float)$stmtLifePays->fetchColumn();

        $lifetimeBalance = $lifetimeCredit - $lifetimePaid;

        sendJSON([
            'success' => true,
            'customer' => [
                'id' => (int)$customer['id'],
                'name' => $customer['name'],
                'phone' => $customer['phone'] ?? '',
                'customer_type' => $customer['customer_type'],
                'address' => $customer['address'] ?? '',
                'credit_limit' => (float)$customer['credit_limit'],
                'notes' => $customer['notes'] ?? '',
                'lifetime_credit' => $lifetimeCredit,
                'lifetime_paid' => $lifetimePaid,
                'lifetime_balance' => $lifetimeBalance
            ],
            'opening_balance' => $openingBalance,
            'period_debit' => $totalPeriodDebit,
            'period_credit' => $totalPeriodCredit,
            'closing_balance' => $runningBal,
            'transactions' => $allTx,
            'bills' => $rawBills,
            'payments' => $rawPays,
            'filter' => [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    // -------------------------------------------------------------------------
    // 8. GET ITEM BREAKDOWN OF A SINGLE BILL
    // -------------------------------------------------------------------------
    else if ($action === 'get_bill_details') {
        $billId = (int)($_GET['bill_id'] ?? 0);
        $stmt = $db->prepare("
            SELECT 
                b.*,
                c.name as customer_name,
                c.phone as customer_phone,
                u.username as cashier_name
            FROM customer_khata_bills b
            JOIN customers c ON b.customer_id = c.id
            LEFT JOIN users u ON b.created_by = u.id
            WHERE b.id = ?
        ");
        $stmt->execute([$billId]);
        $bill = $stmt->fetch();

        if (!$bill) {
            sendJSON(['success' => false, 'message' => 'Bill not found.'], 404);
        }

        $stmtItems = $db->prepare("
            SELECT bi.*, p.barcode 
            FROM customer_khata_bill_items bi 
            LEFT JOIN products p ON bi.product_id = p.id
            WHERE bi.bill_id = ? 
            ORDER BY bi.id ASC
        ");
        $stmtItems->execute([$billId]);
        $bill['items'] = $stmtItems->fetchAll();

        // Check if there was a counter cash down payment associated with this bill
        $stmtDp = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM customer_khata_payments WHERE customer_id = ? AND notes LIKE ? AND status = 'active'");
        $stmtDp->execute([$bill['customer_id'], "%{$bill['bill_number']}%"]);
        $bill['down_payment'] = (float)$stmtDp->fetchColumn();

        sendJSON(['success' => true, 'bill' => $bill]);
    }

    // -------------------------------------------------------------------------
    // 9. VOID BILL (With optional stock inventory restoration)
    // -------------------------------------------------------------------------
    else if ($action === 'void_bill') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
        }
        if (!isAdmin() && !hasPermission('VOID_BILL')) {
            sendJSON(['success' => false, 'message' => 'Unauthorized: Only Managers can void credit bills.'], 403);
        }

        $billId = (int)($_POST['bill_id'] ?? 0);
        $reason = trim(sanitize($_POST['void_reason'] ?? 'Voided by manager'));

        $stmt = $db->prepare("SELECT * FROM customer_khata_bills WHERE id = ?");
        $stmt->execute([$billId]);
        $bill = $stmt->fetch();

        if (!$bill || $bill['status'] === 'voided') {
            sendJSON(['success' => false, 'message' => 'Bill not found or already voided.'], 404);
        }

        $db->beginTransaction();

        // 1. Mark bill voided
        $stmtVoid = $db->prepare("
            UPDATE customer_khata_bills 
            SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = NOW() 
            WHERE id = ?
        ");
        $stmtVoid->execute([$reason, $userId, $billId]);

        // Sync linked POS sale if this was created via POS
        if (!empty($bill['sale_id'])) {
            $db->prepare("UPDATE sales SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = CURRENT_TIMESTAMP WHERE id = ?")
               ->execute(["Voided via Khata: $reason", $userId, $bill['sale_id']]);
        }

        // 2. Restore stock for items that had stock deducted
        $stmtItems = $db->prepare("SELECT * FROM customer_khata_bill_items WHERE bill_id = ? AND stock_deducted = 1 AND product_id IS NOT NULL");
        $stmtItems->execute([$billId]);
        $stockItems = $stmtItems->fetchAll();

        $stmtRestore = $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $stmtMv = $db->prepare("
            INSERT INTO stock_movements (product_id, quantity, movement_type, reference_id, reason, user_id)
            VALUES (?, ?, 'khata_void_reversal', ?, ?, ?)
        ");

        foreach ($stockItems as $it) {
            $stmtRestore->execute([$it['quantity'], $it['product_id']]);
            $stmtMv->execute([
                $it['product_id'],
                $it['quantity'],
                $billId,
                "Restored stock from voided Khata Bill #{$bill['bill_number']}",
                $userId
            ]);
        }

        $db->commit();

        sendJSON(['success' => true, 'message' => "Bill #{$bill['bill_number']} voided and balance adjusted."]);
    }

    // -------------------------------------------------------------------------
    // 10. VOID PAYMENT
    // -------------------------------------------------------------------------
    else if ($action === 'void_payment') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJSON(['success' => false, 'message' => 'Invalid request method.'], 405);
        }
        if (!isAdmin() && !hasPermission('VOID_BILL')) {
            sendJSON(['success' => false, 'message' => 'Unauthorized: Only Managers can void payments.'], 403);
        }

        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $reason = trim(sanitize($_POST['void_reason'] ?? 'Voided by manager'));

        $stmt = $db->prepare("SELECT * FROM customer_khata_payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        $pay = $stmt->fetch();

        if (!$pay || $pay['status'] === 'voided') {
            sendJSON(['success' => false, 'message' => 'Payment receipt not found or already voided.'], 404);
        }

        $stmtVoid = $db->prepare("
            UPDATE customer_khata_payments 
            SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = NOW() 
            WHERE id = ?
        ");
        $stmtVoid->execute([$reason, $userId, $paymentId]);

        sendJSON(['success' => true, 'message' => "Payment receipt #{$pay['receipt_number']} voided."]);
    }

    else {
        sendJSON(['success' => false, 'message' => "Invalid action: '$action'"], 400);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
