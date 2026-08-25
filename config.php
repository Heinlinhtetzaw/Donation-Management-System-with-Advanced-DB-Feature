<?php
// Secure session settings
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (PHP_SAPI !== 'cli') {
    session_start();
}

require_once __DIR__ . '/app/helpers.php';

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dmssystem');

// Admin signup invite code
// Leave empty to allow first admin creation, then generate/store a one-time code.
define('ADMIN_INVITE_CODE', '');

function get_admin_invite_record() {
    $code = trim(ADMIN_INVITE_CODE);
    if ($code !== '') {
        return ['code' => $code, 'used' => false];
    }
    $path = __DIR__ . '/data/admin_invite_code.json';
    if (is_readable($path)) {
        $data = json_decode(file_get_contents($path), true);
        if (is_array($data)) {
            return [
                'code' => isset($data['code']) ? (string) $data['code'] : '',
                'used' => !empty($data['used']),
            ];
        }
    }
    return ['code' => '', 'used' => false];
}

function generate_admin_invite_code() {
    return bin2hex(random_bytes(4));
}

function store_admin_invite_record($code, $used = false) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $payload = json_encode(['code' => $code, 'used' => (bool) $used], JSON_PRETTY_PRINT);
    file_put_contents($dir . '/admin_invite_code.json', $payload);
}

function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return 'unknown';
}

function load_login_attempts() {
    $path = __DIR__ . '/data/login_attempts.json';
    if (is_readable($path)) {
        $data = json_decode(file_get_contents($path), true);
        if (is_array($data)) {
            return $data;
        }
    }
    return [];
}

function save_login_attempts(array $data) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $payload = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($dir . '/login_attempts.json', $payload, LOCK_EX);
}

// Create connection function
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('Database connection failed: ' . $conn->connect_error);
        http_response_code(500);
        exit('The service is temporarily unavailable. Please try again later.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
?> 
