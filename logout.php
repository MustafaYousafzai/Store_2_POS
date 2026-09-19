<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helper.php';

if (isLoggedIn()) {
    logAudit('user_logout', 'users', $_SESSION['user_id'], null, null, 'User logged out via logout.php.');
}

// 1. Wipe session superglobal completely
$_SESSION = [];

// 2. Identify all possible session cookies to destroy
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

// 3. Invalidate cookies in browser headers for both root and current subpath
foreach ($cookiesToFlush as $cName) {
    setcookie($cName, '', time() - 86400, '/');
    setcookie($cName, '', time() - 86400);
}

// 4. Destroy active server-side session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 5. Clean redirect to login page
redirect('/auth/login.php');
exit;

