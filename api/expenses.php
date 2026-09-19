<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

// Enforce login and permission
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized. Please login.'], 401);
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';
$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        requirePermission('VIEW_EXPENSES');
        
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $payment_source = isset($_GET['payment_source']) ? sanitize($_GET['payment_source']) : '';
        
        $where = ["1=1"];
        $params = [];
        
        // If cashier / staff, strictly restrict view to only their own logged records
        if (!isAdmin()) {
            $where[] = "e.created_by = ?";
            $params[] = $_SESSION['user_id'];
        } else {
            // Admin can optionally filter by specific cashier / user
            $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
            if ($user_id > 0) {
                $where[] = "e.created_by = ?";
                $params[] = $user_id;
            }
        }
        
        if ($category_id > 0) {
            $where[] = "e.category_id = ?";
            $params[] = $category_id;
        }
        
        if (!empty($status)) {
            $where[] = "e.status = ?";
            $params[] = $status;
        }
        
        if (!empty($payment_source)) {
            $where[] = "e.payment_source = ?";
            $params[] = $payment_source;
        }
        
        if (!empty($start_date) && !empty($end_date)) {
            $where[] = "e.payment_date BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        } else if (!empty($start_date)) {
            $where[] = "e.payment_date >= ?";
            $params[] = $start_date;
        } else if (!empty($end_date)) {
            $where[] = "e.payment_date <= ?";
            $params[] = $end_date;
        }
        
        $whereClause = implode(" AND ", $where);
        
        $query = "
            SELECT e.*, ec.name as category_name, ec.scope as category_scope, ec.icon as category_icon, 
                   u.username as creator_name, u.role as creator_role, 
                   v.username as voided_by_name
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            JOIN users u ON e.created_by = u.id
            LEFT JOIN users v ON e.voided_by = v.id
            WHERE $whereClause
            ORDER BY e.payment_date DESC, e.id DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'expenses' => $expenses]);
    }
    
    else if ($action === 'list_categories') {
        requirePermission('VIEW_EXPENSES');
        
        if (!isAdmin()) {
            // Cashiers only see staff daily counter categories: Meals (1st), Misc (2nd), Tea (3rd)
            $stmt = $db->query("SELECT * FROM expense_categories WHERE status = 'active' AND scope = 'all_staff' ORDER BY sort_order ASC, id ASC");
        } else {
            // Admin sees all categories ordered by scope and sort_order
            $stmt = $db->query("SELECT * FROM expense_categories WHERE status = 'active' ORDER BY scope ASC, sort_order ASC, id ASC");
        }
        $cats = $stmt->fetchAll();
        sendJSON(['success' => true, 'categories' => $cats, 'is_admin' => isAdmin()]);
    }
    
    else if ($action === 'list_users' && isAdmin()) {
        requirePermission('VIEW_EXPENSES');
        $stmt = $db->query("SELECT id, username, role FROM users WHERE status = 'active' ORDER BY username ASC");
        sendJSON(['success' => true, 'users' => $stmt->fetchAll()]);
    }
    
    else if ($action === 'check_monthly_overhead' && isAdmin()) {
        requirePermission('VIEW_EXPENSES');
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $month = isset($_GET['month']) ? sanitize($_GET['month']) : date('Y-m');
        
        $stmt = $db->prepare("
            SELECT e.id, e.amount, e.payment_date, e.payment_source, e.receipt_number, ec.name as category_name
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.category_id = ? AND e.status = 'active' AND DATE_FORMAT(e.payment_date, '%Y-%m') = ?
            ORDER BY e.id DESC
            LIMIT 1
        ");
        $stmt->execute([$category_id, $month]);
        $existing = $stmt->fetch();
        
        sendJSON(['success' => true, 'exists' => !empty($existing), 'expense' => $existing ?: null]);
    }
    
    else if ($action === 'summary_kpi') {
        requirePermission('VIEW_EXPENSES');
        
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');
        
        // Scope restriction for cashier: strictly their own logged records
        $scopeFilter = "";
        $scopeParams = [];
        if (!isAdmin()) {
            $scopeFilter = "AND e.created_by = ?";
            $scopeParams = [$_SESSION['user_id']];
        }
        
        // 1. Today's Total Expenses
        $todayStmt = $db->prepare("
            SELECT COALESCE(SUM(e.amount), 0) as total, COUNT(e.id) as count
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.status = 'active' AND e.payment_date = ? $scopeFilter
        ");
        $todayStmt->execute(array_merge([$today], $scopeParams));
        $todayData = $todayStmt->fetch();
        
        // 2. Today's Drawer Cash Outflow
        $drawerStmt = $db->prepare("
            SELECT COALESCE(SUM(e.amount), 0) as total
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.status = 'active' AND e.payment_date = ? AND e.payment_source = 'drawer_cash' $scopeFilter
        ");
        $drawerStmt->execute(array_merge([$today], $scopeParams));
        $drawerTotal = (float)$drawerStmt->fetchColumn();
        
        // 3. This Month's Total Expenses
        $monthStmt = $db->prepare("
            SELECT COALESCE(SUM(e.amount), 0) as total, COUNT(e.id) as count
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.status = 'active' AND DATE_FORMAT(e.payment_date, '%Y-%m') = ? $scopeFilter
        ");
        $monthStmt->execute(array_merge([$currentMonth], $scopeParams));
        $monthData = $monthStmt->fetch();
        
        // 4. Breakdown by Category (Today)
        $catStmt = $db->prepare("
            SELECT ec.name, ec.icon, ec.scope, COALESCE(SUM(e.amount), 0) as total, COUNT(e.id) as count
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.status = 'active' AND e.payment_date = ? $scopeFilter
            GROUP BY ec.id
            ORDER BY total DESC
            LIMIT 6
        ");
        $catStmt->execute(array_merge([$today], $scopeParams));
        $catBreakdown = $catStmt->fetchAll();
        
        sendJSON([
            'success' => true,
            'today_total' => (float)$todayData['total'],
            'today_count' => (int)$todayData['count'],
            'today_drawer_cash' => $drawerTotal,
            'month_total' => (float)$monthData['total'],
            'month_count' => (int)$monthData['count'],
            'cat_breakdown' => $catBreakdown
        ]);
    }
    
    else if ($action === 'get') {
        requirePermission('VIEW_EXPENSES');
        $id = (int)$_GET['id'];
        
        $stmt = $db->prepare("
            SELECT e.*, ec.name as category_name, ec.scope as category_scope, ec.icon as category_icon, 
                   u.username as creator_name, v.username as voided_by_name
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            JOIN users u ON e.created_by = u.id
            LEFT JOIN users v ON e.voided_by = v.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $expense = $stmt->fetch();
        
        if ($expense) {
            sendJSON(['success' => true, 'expense' => $expense]);
        } else {
            sendJSON(['success' => false, 'message' => 'Expense not found.'], 404);
        }
    }
} 

else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        requirePermission('VIEW_EXPENSES');
        
        $category_id = (int)$_POST['category_id'];
        $amount = (float)$_POST['amount'];
        $payment_date = !empty($_POST['payment_date']) ? sanitize($_POST['payment_date']) : date('Y-m-d');
        $payment_source = sanitize($_POST['payment_source'] ?? 'drawer_cash');
        $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
        $description = trim(sanitize($_POST['description'] ?? ''));
        $receipt_number = trim(sanitize($_POST['receipt_number'] ?? ''));
        
        if ($category_id <= 0 || $amount <= 0) {
            sendJSON(['success' => false, 'message' => 'Valid category and positive amount are required.'], 400);
        }
        
        // Validate category exists and enforce role scope
        $catStmt = $db->prepare("SELECT * FROM expense_categories WHERE id = ? AND status = 'active'");
        $catStmt->execute([$category_id]);
        $cat = $catStmt->fetch();
        
        if (!$cat) {
            sendJSON(['success' => false, 'message' => 'Selected category is invalid or inactive.'], 400);
        }
        
        // Anti-privilege escalation: Cashier cannot post to admin_only categories (e.g. Rent, Overheads)
        if (!isAdmin() && $cat['scope'] === 'admin_only') {
            sendJSON(['success' => false, 'message' => 'Only Administrators can record overhead expenses such as rent and utility bills.'], 403);
        }
        
        // Cashiers default to drawer cash
        if (!isAdmin()) {
            $payment_source = 'drawer_cash';
            $payment_method = 'cash';
        }
        
        // Fallback description if empty
        if (empty($description)) {
            $description = $cat['name'];
        }
        
        // Validate source and method
        if (!in_array($payment_source, ['drawer_cash', 'bank_transfer', 'card', 'owner_pocket'])) {
            $payment_source = 'drawer_cash';
        }
        if (!in_array($payment_method, ['cash', 'card', 'bank'])) {
            $payment_method = 'cash';
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO expenses (category_id, amount, description, payment_method, payment_source, payment_date, receipt_number, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)
            ");
            $stmt->execute([
                $category_id,
                $amount,
                $description,
                $payment_method,
                $payment_source,
                $payment_date,
                $receipt_number ?: null,
                $_SESSION['user_id']
            ]);
            $expenseId = $db->lastInsertId();
            
            // Voucher code (e.g. EXP-2026-0045)
            $voucherCode = 'EXP-' . date('Ymd') . '-' . str_pad($expenseId, 4, '0', STR_PAD_LEFT);
            
            logAudit('expense_create', 'expenses', $expenseId, null, [
                'category_id' => $category_id,
                'category_name' => $cat['name'],
                'amount' => $amount,
                'payment_source' => $payment_source,
                'payment_date' => $payment_date
            ], 'Recorded store expense.');
            
            sendJSON([
                'success' => true, 
                'message' => "Expense of Rs. " . number_format($amount, 2) . " recorded successfully.",
                'expense_id' => $expenseId,
                'voucher_code' => $voucherCode,
                'category_name' => $cat['name'],
                'amount' => $amount,
                'payment_date' => $payment_date,
                'payment_source' => $payment_source,
                'description' => $description,
                'creator_name' => $_SESSION['username']
            ]);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Failed to record expense: ' . $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'void') {
        // Enforce administrative override to void financial logs
        if (!isAdmin()) {
            sendJSON(['success' => false, 'message' => 'Permission Denied: Only Administrators can cancel or void expense records.'], 403);
        }
        
        $id = (int)$_POST['id'];
        $reason = trim(sanitize($_POST['reason']));
        
        if ($id <= 0 || empty($reason)) {
            sendJSON(['success' => false, 'message' => 'Expense ID and cancellation reason are required.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("SELECT * FROM expenses WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $expense = $stmt->fetch();
            
            if (!$expense) {
                throw new Exception("Expense record not found.");
            }
            
            if ($expense['status'] === 'voided') {
                throw new Exception("Expense record is already voided.");
            }
            
            $up = $db->prepare("
                UPDATE expenses 
                SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $up->execute([$reason, $_SESSION['user_id'], $id]);
            
            logAudit('expense_void', 'expenses', $id, $expense, ['status' => 'voided', 'reason' => $reason], 'Expense voided.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Expense record voided successfully.']);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'add_category') {
        if (!isAdmin()) {
            sendJSON(['success' => false, 'message' => 'Only Administrators can create new expense categories.'], 403);
        }
        
        $name = trim(sanitize($_POST['name']));
        $scope = sanitize($_POST['scope'] ?? 'all_staff');
        $icon = sanitize($_POST['icon'] ?? 'fa-receipt');
        
        if (empty($name)) {
            sendJSON(['success' => false, 'message' => 'Category name is required.'], 400);
        }
        if (!in_array($scope, ['all_staff', 'admin_only'])) {
            $scope = 'all_staff';
        }
        
        // Uniqueness check
        $chk = $db->prepare("SELECT id FROM expense_categories WHERE name = ?");
        $chk->execute([$name]);
        $exists = $chk->fetch();
        
        if ($exists) {
            sendJSON(['success' => false, 'message' => 'Category already exists.', 'category_id' => $exists['id']]);
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO expense_categories (name, scope, icon, status) VALUES (?, ?, ?, 'active')");
            $stmt->execute([$name, $scope, $icon]);
            $catId = $db->lastInsertId();
            
            logAudit('expense_category_create', 'expense_categories', $catId, null, ['name' => $name, 'scope' => $scope], 'Created expense category.');
            
            sendJSON(['success' => true, 'message' => 'Category added successfully.', 'category_id' => $catId]);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Failed to add category: ' . $e->getMessage()], 500);
        }
    }
}
