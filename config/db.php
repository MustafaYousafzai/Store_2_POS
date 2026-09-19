<?php
// Timezone and error reporting configuration
date_default_timezone_set('Asia/Karachi');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Load .env file if available in root directory
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Enterprise-grade Session isolation (guarantees separate instances never collide and session is deterministic)
if (!defined('APP_SESSION_NAME')) {
    $normalizedPath = strtolower(str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__)));
    define('APP_SESSION_NAME', 'POS_SESS_' . substr(md5($normalizedPath), 0, 10));
}

if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_name(APP_SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    } else {
        @session_start();
    }
}

// Store Configuration
if (!defined('STORE_NAME')) {
    define('STORE_NAME', getenv('STORE_NAME') ?: 'One Dollar Shop');
}
if (!defined('STORE_TAGLINE')) {
    define('STORE_TAGLINE', getenv('STORE_TAGLINE') ?: 'Wholesale & Retail Discount Center');
}
if (!defined('STORE_CURRENCY')) {
    define('STORE_CURRENCY', getenv('STORE_CURRENCY') ?: 'Rs.');
}
if (!defined('STORE_ADDRESS')) {
    define('STORE_ADDRESS', getenv('STORE_ADDRESS') ?: 'McConaghey Road, Quetta');
}
if (!defined('STORE_PHONE')) {
    define('STORE_PHONE', getenv('STORE_PHONE') ?: '0307-2681893');
}
if (!defined('STORE_LOGO')) {
    define('STORE_LOGO', getenv('STORE_LOGO') ?: 'one_dollar_shop_logo.png');
}

// Database Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'store_2_pos');
}

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
        
        // Sync database session timezone with PHP
        $pdo->exec("SET time_zone = '+05:00'");
    }
    return $pdo;
}
