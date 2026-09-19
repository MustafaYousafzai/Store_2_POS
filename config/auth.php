<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper.php';

if (ob_get_level() === 0) {
    ob_start();
}

/**
 * Check if a user is logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['status']) && $_SESSION['status'] === 'active';
}

/**
 * Enforce authentication. Redirects to login if not logged in.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
}

/**
 * Check if the logged-in user is an administrator.
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Checks if the current user has a specific granular permission key.
 */
function hasPermission($permissionKey) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admins automatically bypass granular permission checks and have full access
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    
    // Load permissions from DB into session if not yet cached
    if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
        refreshUserPermissions($_SESSION['user_id']);
    }
    
    return isset($_SESSION['permissions']) && is_array($_SESSION['permissions']) && in_array($permissionKey, $_SESSION['permissions']);
}

/**
 * Enforces permission. Returns a 403 API response or displays an error page if unauthorized.
 */
function requirePermission($permissionKey) {
    if (!isLoggedIn()) {
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
            exit;
        } else {
            redirect('/auth/login.php');
        }
    }
    
    if (!hasPermission($permissionKey)) {
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Forbidden: Lacks permission '$permissionKey'"]);
            exit;
        } else {
            http_response_code(403);
            die("Error 403: You do not have permission ('$permissionKey') to access this resource.");
        }
    }
}

/**
 * Refresh user permissions from the database and cache in session.
 */
function refreshUserPermissions($userId) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT p.perm_key 
        FROM user_permissions up
        JOIN permissions p ON up.permission_id = p.id
        WHERE up.user_id = ?
    ");
    $stmt->execute([$userId]);
    $_SESSION['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
