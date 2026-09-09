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
function config_env($name, $default = '') {
    $value = getenv($name);
    return $value === false ? $default : $value;
}

define('DB_HOST', config_env('DMS_DB_HOST', 'localhost'));
define('DB_PORT', (int) config_env('DMS_DB_PORT', '3306'));
define('DB_USER', config_env('DMS_DB_USER', 'root'));
define('DB_PASS', config_env('DMS_DB_PASS', ''));
define('DB_NAME', config_env('DMS_DB_NAME', 'dmssystem'));

// Fallback admin signup invite code used only when no runtime invite record exists.
// Leave empty to allow first admin creation, then generate/store a one-time code.
define('ADMIN_INVITE_CODE', config_env('DMS_ADMIN_INVITE_CODE', ''));
define('TRUST_PROXY_HEADERS', filter_var(config_env('DMS_TRUST_PROXY_HEADERS', 'false'), FILTER_VALIDATE_BOOLEAN));

function admin_invite_record_path() {
    return __DIR__ . '/data/admin_invite_code.json';
}

function decode_admin_invite_record($payload) {
    $data = json_decode((string) $payload, true);
    if (!is_array($data) || !isset($data['code']) || !is_string($data['code'])) {
        return null;
    }

    return [
        'code' => $data['code'],
        'used' => !empty($data['used']),
    ];
}

function get_admin_invite_record() {
    $path = admin_invite_record_path();
    if (is_file($path)) {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['code' => '', 'used' => true];
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                return ['code' => '', 'used' => true];
            }
            $record = decode_admin_invite_record(stream_get_contents($handle));
            flock($handle, LOCK_UN);
            return $record ?? ['code' => '', 'used' => true];
        } finally {
            fclose($handle);
        }
    }

    return ['code' => trim(ADMIN_INVITE_CODE), 'used' => false];
}

function generate_admin_invite_code() {
    return bin2hex(random_bytes(4));
}

function store_admin_invite_record($code, $used = false) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('The invite-code store is unavailable.');
    }

    $path = admin_invite_record_path();
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new RuntimeException('The invite-code store is unavailable.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('The invite-code store could not be locked.');
        }
        write_admin_invite_record($handle, $code, $used);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
}

function write_admin_invite_record($handle, $code, $used) {
    $payload = json_encode(
        ['code' => (string) $code, 'used' => (bool) $used],
        JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
    );
    rewind($handle);
    if (!ftruncate($handle, 0) || fwrite($handle, $payload) !== strlen($payload) || !fflush($handle)) {
        throw new RuntimeException('The invite-code store could not be updated.');
    }
}

function consume_admin_invite_code($submittedCode, callable $onValid) {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('The invite-code store is unavailable.');
    }

    $path = admin_invite_record_path();
    $recordExisted = is_file($path);
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new RuntimeException('The invite-code store is unavailable.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('The invite-code store could not be locked.');
        }

        rewind($handle);
        $record = decode_admin_invite_record(stream_get_contents($handle));
        if ($record === null && !$recordExisted) {
            $record = ['code' => trim(ADMIN_INVITE_CODE), 'used' => false];
        }
        if ($record === null || $record['code'] === '') {
            throw new DomainException('Invite code not configured. Ask an admin.');
        }
        if ($record['used']) {
            throw new DomainException('Invite code already used. Ask an admin.');
        }
        if (!hash_equals($record['code'], (string) $submittedCode)) {
            throw new DomainException('Invalid invite code.');
        }

        $result = $onValid();
        write_admin_invite_record($handle, $record['code'], true);
        flock($handle, LOCK_UN);
        return $result;
    } finally {
        fclose($handle);
    }
}

function get_client_ip() {
    if (TRUST_PROXY_HEADERS && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
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
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        if (PHP_SAPI === 'cli') {
            throw new RuntimeException('Database connection failed.', 0, $exception);
        }
        http_response_code(500);
        exit('The service is temporarily unavailable. Please try again later.');
    }

    return $conn;
}
