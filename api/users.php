<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is logged in
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized. Please login.'], 401);
}

// Ensure user has permission to manage users (Admin only)
if (!isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Access denied: Only Administrators can manage user accounts.'], 403);
}

// Standard helper for automatic role-based permission assignment
function assignRolePermissions($db, $userId, $role) {
    $delPermStmt = $db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
    $delPermStmt->execute([$userId]);
    
    if ($role === 'admin') {
        $allPerms = $db->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        $permInsert = $db->prepare("INSERT INTO user_permissions (user_id, permission_id) VALUES (?, ?)");
        foreach ($allPerms as $pid) {
            $permInsert->execute([$userId, (int)$pid]);
        }
    } else {
        // Standard pre-defined Cashier permissions: POS, Cart, Hold, View Sales, Print, Refund, Exchange, Khata, Expenses
        $cashierPermKeys = ['CREATE_SALE', 'EDIT_CART', 'HOLD_BILL', 'VIEW_SALES', 'PRINT_BILL', 'REFUND', 'EXCHANGE', 'MANAGE_KHATA', 'VIEW_EXPENSES'];
        $inClause = "'" . implode("','", $cashierPermKeys) . "'";
        $defStmt = $db->query("SELECT id FROM permissions WHERE perm_key IN ($inClause)");
        $defIds = $defStmt->fetchAll(PDO::FETCH_COLUMN);
        $permInsert = $db->prepare("INSERT INTO user_permissions (user_id, permission_id) VALUES (?, ?)");
        foreach ($defIds as $pid) {
            $permInsert->execute([$userId, (int)$pid]);
        }
    }
}

$db = getDBConnection();
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

// -------------------------------------------------------------
// GET: List All Users
// -------------------------------------------------------------
if ($action === 'list') {
    try {
        $sql = "
            SELECT 
                u.id, 
                u.username, 
                u.role, 
                u.status, 
                u.created_at, 
                u.updated_at,
                COALESCE((
                    SELECT GROUP_CONCAT(permission_id ORDER BY permission_id ASC) 
                    FROM user_permissions 
                    WHERE user_id = u.id
                ), '') AS permission_ids,
                (
                    (SELECT COUNT(*) FROM sales WHERE cashier_id = u.id) +
                    (SELECT COUNT(*) FROM expenses WHERE created_by = u.id) +
                    (SELECT COUNT(*) FROM stock_movements WHERE user_id = u.id) +
                    (SELECT COUNT(*) FROM employee_transactions WHERE created_by = u.id) +
                    (SELECT COUNT(*) FROM audit_logs WHERE user_id = u.id)
                ) AS tx_count
            FROM users u
            ORDER BY u.id ASC
        ";
        
        $stmt = $db->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as &$user) {
            $user['id'] = (int)$user['id'];
            $user['tx_count'] = (int)$user['tx_count'];
            $user['has_transactions'] = ($user['tx_count'] > 0);
            $user['is_self'] = ($user['id'] === (int)$_SESSION['user_id']);
            $user['permissions'] = !empty($user['permission_ids']) ? array_map('intval', explode(',', $user['permission_ids'])) : [];
        }
        unset($user);
        
        sendJSON([
            'success' => true, 
            'users' => $users, 
            'current_user_id' => (int)$_SESSION['user_id']
        ]);
    } catch (Exception $e) {
        sendJSON(['success' => false, 'message' => 'Failed to load users: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// GET: All System Permissions
// -------------------------------------------------------------
else if ($action === 'permissions') {
    try {
        $stmt = $db->query("SELECT id, perm_key, description FROM permissions ORDER BY id ASC");
        $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($perms as &$p) {
            $p['id'] = (int)$p['id'];
        }
        unset($p);
        sendJSON(['success' => true, 'permissions' => $perms]);
    } catch (Exception $e) {
        sendJSON(['success' => false, 'message' => 'Failed to fetch permissions: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// GET: Single User by ID
// -------------------------------------------------------------
else if ($action === 'get') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid user ID.'], 400);
    }
    
    try {
        $stmt = $db->prepare("SELECT id, username, role, status, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            sendJSON(['success' => false, 'message' => 'User not found.'], 404);
        }
        
        $user['id'] = (int)$user['id'];
        $user['is_self'] = ($user['id'] === (int)$_SESSION['user_id']);
        
        $permStmt = $db->prepare("SELECT permission_id FROM user_permissions WHERE user_id = ? ORDER BY permission_id ASC");
        $permStmt->execute([$id]);
        $user['permissions'] = array_map('intval', $permStmt->fetchAll(PDO::FETCH_COLUMN));
        
        sendJSON(['success' => true, 'user' => $user]);
    } catch (Exception $e) {
        sendJSON(['success' => false, 'message' => 'Error retrieving user: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// POST: Create New User
// -------------------------------------------------------------
else if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'staff';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
    $perms = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Validations
    if (empty($username)) {
        sendJSON(['success' => false, 'message' => 'Username is required.'], 400);
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        sendJSON(['success' => false, 'message' => 'Username must be 3-50 characters long and contain only letters, numbers, hyphens, underscores, or dots.'], 400);
    }
    if (empty($password) || strlen($password) < 4) {
        sendJSON(['success' => false, 'message' => 'Password must be at least 4 characters long.'], 400);
    }
    if (!in_array($role, ['admin', 'staff'])) {
        sendJSON(['success' => false, 'message' => 'Invalid role specified.'], 400);
    }
    if (!in_array($status, ['active', 'inactive'])) {
        sendJSON(['success' => false, 'message' => 'Invalid status specified.'], 400);
    }
    
    try {
        // Check duplicate username (case-insensitive)
        $dupStmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
        $dupStmt->execute([$username]);
        if ($dupStmt->fetch()) {
            sendJSON(['success' => false, 'message' => 'Username "' . htmlspecialchars($username) . '" is already taken. Please choose another.'], 400);
        }
        
        $db->beginTransaction();
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $insertStmt = $db->prepare("INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$username, $passwordHash, $role, $status]);
        $newUserId = (int)$db->lastInsertId();
        
        // Automatically assign role permissions (Full for Admin, Fixed counter permissions for Cashier)
        assignRolePermissions($db, $newUserId, $role);
        
        logAudit('user_created', 'users', $newUserId, null, [
            'username' => $username,
            'role' => $role,
            'status' => $status
        ], 'Created user account: ' . $username . ' (' . $role . ')');
        
        $db->commit();
        
        sendJSON([
            'success' => true, 
            'message' => 'User "' . htmlspecialchars($username) . '" created successfully.',
            'user_id' => $newUserId
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sendJSON(['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// POST: Update User
// -------------------------------------------------------------
else if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'staff';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $perms = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
    
    if ($id <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid user ID.'], 400);
    }
    if (empty($username)) {
        sendJSON(['success' => false, 'message' => 'Username is required.'], 400);
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        sendJSON(['success' => false, 'message' => 'Username must be 3-50 characters long and contain only letters, numbers, hyphens, underscores, or dots.'], 400);
    }
    if (!in_array($role, ['admin', 'staff'])) {
        sendJSON(['success' => false, 'message' => 'Invalid role specified.'], 400);
    }
    if (!in_array($status, ['active', 'inactive'])) {
        sendJSON(['success' => false, 'message' => 'Invalid status specified.'], 400);
    }
    
    try {
        // Fetch existing user
        $fetchStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $fetchStmt->execute([$id]);
        $existing = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            sendJSON(['success' => false, 'message' => 'User not found.'], 404);
        }
        
        // Check duplicate username on another user
        $dupStmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?");
        $dupStmt->execute([$username, $id]);
        if ($dupStmt->fetch()) {
            sendJSON(['success' => false, 'message' => 'Username "' . htmlspecialchars($username) . '" is already taken by another account.'], 400);
        }
        
        // Safeguards on Admin & Status
        $activeAdminCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
        
        // Cannot demote last active admin to staff
        if ($existing['role'] === 'admin' && $role !== 'admin' && $existing['status'] === 'active' && $activeAdminCount <= 1) {
            sendJSON(['success' => false, 'message' => 'Cannot change role. At least one active Administrator must remain in the system.'], 400);
        }
        
        // Cannot deactivate own account
        if ($id === (int)$_SESSION['user_id'] && $status === 'inactive') {
            sendJSON(['success' => false, 'message' => 'You cannot deactivate your own active account.'], 400);
        }
        
        // Cannot deactivate last active admin
        if ($existing['role'] === 'admin' && $status === 'inactive' && $existing['status'] === 'active' && $activeAdminCount <= 1) {
            sendJSON(['success' => false, 'message' => 'Cannot deactivate the only active Administrator account.'], 400);
        }
        
        $db->beginTransaction();
        
        // Update password if supplied
        if (!empty($password)) {
            if (strlen($password) < 4) {
                sendJSON(['success' => false, 'message' => 'Password must be at least 4 characters long.'], 400);
            }
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $updateStmt = $db->prepare("UPDATE users SET username = ?, role = ?, status = ?, password_hash = ? WHERE id = ?");
            $updateStmt->execute([$username, $role, $status, $passwordHash, $id]);
        } else {
            $updateStmt = $db->prepare("UPDATE users SET username = ?, role = ?, status = ? WHERE id = ?");
            $updateStmt->execute([$username, $role, $status, $id]);
        }
        
        // Automatically assign role permissions (Full for Admin, Fixed counter permissions for Cashier)
        assignRolePermissions($db, $id, $role);
        
        // If updating currently logged in user, refresh session variables
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['status'] = $status;
            refreshUserPermissions($id);
        }
        
        logAudit('user_updated', 'users', $id, [
            'username' => $existing['username'],
            'role' => $existing['role'],
            'status' => $existing['status']
        ], [
            'username' => $username,
            'role' => $role,
            'status' => $status
        ], 'Updated user account: ' . $username);
        
        $db->commit();
        
        sendJSON([
            'success' => true, 
            'message' => 'User "' . htmlspecialchars($username) . '" updated successfully.'
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sendJSON(['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// POST: Reset Password
// -------------------------------------------------------------
else if ($action === 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    
    if ($id <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid user ID.'], 400);
    }
    if (empty($newPassword) || strlen($newPassword) < 4) {
        sendJSON(['success' => false, 'message' => 'Password must be at least 4 characters long.'], 400);
    }
    
    try {
        $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            sendJSON(['success' => false, 'message' => 'User not found.'], 404);
        }
        
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->execute([$hash, $id]);
        
        logAudit('admin_reset_password', 'users', $id, null, null, 'Administrator reset password for user: ' . $user['username']);
        
        sendJSON([
            'success' => true, 
            'message' => 'Password for user "' . htmlspecialchars($user['username']) . '" has been reset successfully.'
        ]);
    } catch (Exception $e) {
        sendJSON(['success' => false, 'message' => 'Failed to reset password: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// POST: Toggle Status (Active / Inactive)
// -------------------------------------------------------------
else if ($action === 'toggle_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid user ID.'], 400);
    }
    
    if ($id === (int)$_SESSION['user_id']) {
        sendJSON(['success' => false, 'message' => 'You cannot change the status of your own account.'], 400);
    }
    
    try {
        $stmt = $db->prepare("SELECT id, username, role, status FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            sendJSON(['success' => false, 'message' => 'User not found.'], 404);
        }
        
        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        
        // Safeguard last active admin
        if ($user['role'] === 'admin' && $newStatus === 'inactive') {
            $activeAdminCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
            if ($activeAdminCount <= 1) {
                sendJSON(['success' => false, 'message' => 'Cannot deactivate the only active Administrator account.'], 400);
            }
        }
        
        $updateStmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);
        
        logAudit('toggle_user_status', 'users', $id, ['status' => $user['status']], ['status' => $newStatus], 'Toggled user status to ' . $newStatus);
        
        sendJSON([
            'success' => true, 
            'status' => $newStatus, 
            'message' => 'User "' . htmlspecialchars($user['username']) . '" status changed to ' . ucfirst($newStatus) . '.'
        ]);
    } catch (Exception $e) {
        sendJSON(['success' => false, 'message' => 'Failed to toggle status: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// POST: Delete User Account
// -------------------------------------------------------------
else if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        sendJSON(['success' => false, 'message' => 'Invalid user ID.'], 400);
    }
    
    if ($id === (int)$_SESSION['user_id']) {
        sendJSON(['success' => false, 'message' => 'You cannot delete your own logged-in account.'], 400);
    }
    
    try {
        $stmt = $db->prepare("SELECT id, username, role, status FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            sendJSON(['success' => false, 'message' => 'User not found.'], 404);
        }
        
        // Safeguard last active admin
        if ($user['role'] === 'admin') {
            $activeAdminCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
            if ($activeAdminCount <= 1) {
                sendJSON(['success' => false, 'message' => 'Cannot delete the only active Administrator account.'], 400);
            }
        }
        
        // Let's accurately calculate linked records
        $chkStmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM sales WHERE cashier_id = ? OR voided_by = ?) +
                (SELECT COUNT(*) FROM expenses WHERE created_by = ? OR voided_by = ?) +
                (SELECT COUNT(*) FROM stock_movements WHERE user_id = ?) +
                (SELECT COUNT(*) FROM refunds WHERE cashier_id = ?) +
                (SELECT COUNT(*) FROM exchanges WHERE cashier_id = ?) +
                (SELECT COUNT(*) FROM employee_transactions WHERE created_by = ?) +
                (SELECT COUNT(*) FROM employee_settlements WHERE settled_by = ?) +
                (SELECT COUNT(*) FROM employee_tasks WHERE created_by = ?) +
                (SELECT COUNT(*) FROM audit_logs WHERE user_id = ?) AS total_linked
        ");
        $chkStmt->execute([$id, $id, $id, $id, $id, $id, $id, $id, $id, $id, $id]);
        $totalLinked = (int)$chkStmt->fetchColumn();
        
        if ($totalLinked > 0) {
            // User has linked records -> Deactivate user to preserve database foreign keys and financial history
            $updateStmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $updateStmt->execute([$id]);
            
            logAudit('user_deactivated_on_delete', 'users', $id, ['status' => $user['status']], ['status' => 'inactive'], 'User account deactivated instead of deleted due to linked historical records.');
            
            sendJSON([
                'success' => true, 
                'deactivated' => true, 
                'message' => 'User "' . htmlspecialchars($user['username']) . '" has ' . $totalLinked . ' linked historical records (sales, expenses, or audits). To protect accounting integrity, this account has been deactivated (Disabled) instead of permanently deleted.'
            ]);
        } else {
            // Safe to permanently delete
            $db->beginTransaction();
            
            // Delete user permissions first
            $delPermStmt = $db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
            $delPermStmt->execute([$id]);
            
            // Delete user
            $delStmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $delStmt->execute([$id]);
            
            logAudit('user_deleted', 'users', $id, ['username' => $user['username'], 'role' => $user['role']], null, 'User account permanently deleted: ' . $user['username']);
            
            $db->commit();
            
            sendJSON([
                'success' => true, 
                'deleted' => true, 
                'message' => 'User account "' . htmlspecialchars($user['username']) . '" has been permanently deleted.'
            ]);
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sendJSON(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()], 500);
    }
}

// -------------------------------------------------------------
// Fallback: Invalid Action
// -------------------------------------------------------------
else {
    sendJSON(['success' => false, 'message' => 'Invalid action or request method.'], 400);
}
