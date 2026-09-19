<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : ''; // Keep password un-sanitized to avoid escaping characters
        
        if (empty($username) || empty($password)) {
            sendJSON(['success' => false, 'message' => 'Username and password are required.'], 400);
        }
        
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                sendJSON(['success' => false, 'message' => 'Account is inactive. Please contact Administrator.'], 403);
            }
            
            // Clean slate: completely wipe any prior session data to prevent session fixation or role pollution
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            
            // Set session variables strictly for this user
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['status'];
            
            // Load user permissions into session
            refreshUserPermissions((int)$user['id']);
            
            logAudit('user_login', 'users', (int)$user['id'], null, null, 'User logged in: ' . $user['username'] . ' (' . $user['role'] . ')');
            
            // Strict role-based landing URL: Admin -> Dashboard, Cashier/Staff -> POS Terminal ALWAYS
            $redirectUrl = ($user['role'] === 'admin') ? url('/dashboard/index.php') : url('/pos/index.php');
            
            sendJSON([
                'success' => true, 
                'message' => 'Login successful.',
                'role' => $user['role'],
                'username' => $user['username'],
                'redirect_url' => $redirectUrl
            ]);
        } else {
            sendJSON(['success' => false, 'message' => 'Invalid username or password.'], 401);
        }
    }
    
    else if ($action === 'change_password') {
        if (!isLoggedIn()) {
            sendJSON(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        
        $oldPassword = isset($_POST['old_password']) ? $_POST['old_password'] : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            sendJSON(['success' => false, 'message' => 'All password fields are required.'], 400);
        }
        
        if ($newPassword !== $confirmPassword) {
            sendJSON(['success' => false, 'message' => 'New password and confirmation do not match.'], 400);
        }
        
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($oldPassword, $user['password_hash'])) {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            $updateStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $_SESSION['user_id']]);
            
            logAudit('change_password', 'users', $_SESSION['user_id'], null, null, 'User changed their own password.');
            
            sendJSON(['success' => true, 'message' => 'Password updated successfully.']);
        } else {
            sendJSON(['success' => false, 'message' => 'Incorrect old password.'], 400);
        }
    }
    
    else {
        sendJSON(['success' => false, 'message' => 'Invalid action.'], 400);
    }
} 

else if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'logout') {
        if (isLoggedIn()) {
            logAudit('user_logout', 'users', $_SESSION['user_id'], null, null, 'User logged out via api/auth.php.');
        }
        
        // 1. Wipe session superglobal
        $_SESSION = [];
        
        // 2. Identify and flush all possible session cookies
        $cookiesToFlush = ['PHPSESSID', 'STORE_2_POS_SESS', 'DOLLAR_SHOP_POS_SESS'];
        if (defined('APP_SESSION_NAME')) {
            $cookiesToFlush[] = APP_SESSION_NAME;
        }
        if (function_exists('session_name')) {
            $cookiesToFlush[] = session_name();
        }
        if (!empty($_COOKIE)) {
            foreach (array_keys($_COOKIE) as $cName) {
                if (strpos($cName, 'POS_SESS_') === 0 || stripos($cName, 'SESS') !== false || $cName === 'PHPSESSID') {
                    $cookiesToFlush[] = $cName;
                }
            }
        }
        $cookiesToFlush = array_unique(array_filter($cookiesToFlush));

        foreach ($cookiesToFlush as $cName) {
            setcookie($cName, '', time() - 86400, '/');
            setcookie($cName, '', time() - 86400);
        }

        // 3. Destroy active session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // 4. Return JSON for AJAX or redirect for regular HTTP GET
        if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            sendJSON(['success' => true, 'message' => 'Logged out successfully.', 'redirect_url' => url('/auth/login.php')]);
        }
        
        redirect('/auth/login.php');
        exit;
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid request.'], 400);
    }
}
