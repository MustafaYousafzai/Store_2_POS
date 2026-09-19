<?php
require_once __DIR__ . '/db.php';

// Auto-detect BASE_URL dynamically for 100% portability (works in any subfolder or domain root)
if (!defined('BASE_URL')) {
    if (getenv('APP_URL')) {
        define('BASE_URL', rtrim(getenv('APP_URL'), '/'));
    } else {
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']) : '';
        $appRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
        
        $basePath = '';
        if (!empty($docRoot) && stripos($appRoot, $docRoot) === 0) {
            $basePath = substr($appRoot, strlen($docRoot));
        } else {
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $dirName = basename($appRoot);
            $pos = stripos($scriptName, '/' . $dirName);
            if ($pos !== false) {
                $basePath = substr($scriptName, 0, $pos + strlen($dirName) + 1);
            }
        }
        $basePath = '/' . trim($basePath, '/');
        define('BASE_URL', $basePath === '/' ? '' : $basePath);
    }
}

/**
 * Generate dynamic application URL.
 */
if (!function_exists('url')) {
    function url($path = '') {
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            return BASE_URL === '' ? '/' : BASE_URL;
        }
        return BASE_URL . $path;
    }
}

/**
 * Generate asset URL.
 */
if (!function_exists('asset')) {
    function asset($path = '') {
        return url($path);
    }
}

/**
 * Generate store logo URL.
 */
if (!function_exists('logoUrl')) {
    function logoUrl($logoFile = null) {
        $logo = $logoFile ?: (defined('STORE_LOGO') ? STORE_LOGO : 'one_dollar_shop_logo.png');
        return url('/logo/' . $logo);
    }
}

/**
 * Perform safe HTTP redirect.
 */
if (!function_exists('redirect')) {
    function redirect($path) {
        header("Location: " . url($path));
        exit;
    }
}

/**
 * Send JSON response and exit.
 */
function sendJSON($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Sanitize standard inputs.
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Write a detailed audit log entry.
 */
function logAudit($action, $entityType, $entityId, $oldValues = null, $newValues = null, $reason = null) {
    try {
        $db = getDBConnection();
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to admin/system if not logged in
        
        $oldJSON = $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
        $newJSON = $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, reason)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldJSON,
            $newJSON,
            $reason
        ]);
        return true;
    } catch (Exception $e) {
        // Fail silently in terms of user experience, but log to error log
        error_log("Audit log write failure: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper to determine if a route or page pattern is currently active.
 */
if (!function_exists('isPageActive')) {
    function isPageActive($pattern) {
        $currentPage = $_SERVER['SCRIPT_NAME'] ?? '';
        if (is_array($pattern)) {
            foreach ($pattern as $p) {
                if (strpos($currentPage, $p) !== false) {
                    return true;
                }
            }
            return false;
        }
        return strpos($currentPage, $pattern) !== false;
    }
}
