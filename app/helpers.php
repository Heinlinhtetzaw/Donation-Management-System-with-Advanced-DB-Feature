<?php

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function request_string(array $source, $key, $default = '') {
    if (!array_key_exists($key, $source) || is_array($source[$key])) {
        return $default;
    }

    return trim((string) $source[$key]);
}

function text_excerpt($value, $length) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $isLong = function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8') > $length
        : strlen($value) > $length;
    if (!$isLong) {
        return $value;
    }

    $excerpt = function_exists('mb_substr')
        ? mb_substr($value, 0, $length, 'UTF-8')
        : substr($value, 0, $length);

    return rtrim($excerpt) . '…';
}

function text_limit($value, $length) {
    $value = trim((string) $value);
    if (text_length($value) <= $length) {
        return $value;
    }

    return function_exists('mb_substr')
        ? mb_substr($value, 0, $length, 'UTF-8')
        : substr($value, 0, $length);
}

function text_length($value) {
    return function_exists('mb_strlen')
        ? mb_strlen((string) $value, 'UTF-8')
        : strlen((string) $value);
}

function has_text_length($value, $minimum, $maximum) {
    $length = text_length(trim((string) $value));
    return $length >= $minimum && $length <= $maximum;
}

function normalize_myanmar_digits($value) {
    return strtr((string) $value, [
        '၀' => '0',
        '၁' => '1',
        '၂' => '2',
        '၃' => '3',
        '၄' => '4',
        '၅' => '5',
        '၆' => '6',
        '၇' => '7',
        '၈' => '8',
        '၉' => '9',
    ]);
}

function is_iso_date($value) {
    if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
        return false;
    }

    return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
}

function format_mmk($amount) {
    return number_format((float) $amount, 2) . ' MMK';
}

function execute_prepared(mysqli $conn, $sql, $types = '', array $values = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    return $stmt;
}

function css_class_token($value) {
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9_-]+/', '-', $value);
    return trim($value, '-');
}

function public_asset_path($path, $fallback = 'image/logooo2.jpg') {
    $path = str_replace('\\', '/', trim((string) $path));
    if ($path === '' || strpos($path, '..') !== false) {
        return $fallback;
    }

    if (!preg_match('#^(?:image|uploads)/[A-Za-z0-9._/-]+$#', $path)) {
        return $fallback;
    }

    return $path;
}

function redirect_to($path) {
    header('Location: ' . $path);
    exit();
}

function is_admin_authenticated() {
    return !empty($_SESSION['admin_username']);
}

function require_admin() {
    if (!is_admin_authenticated()) {
        redirect_to('adlogin.php');
    }
}

function require_post_request($fallback) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        redirect_to($fallback);
    }
}

function require_valid_csrf($fallback) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect_to($fallback);
    }
}

function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash($type) {
    if (empty($_SESSION['flash'][$type])) {
        return '';
    }

    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function positive_int($value) {
    $validated = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $validated === false ? null : (int) $validated;
}

function remove_uploaded_file($relativePath) {
    $prefix = 'uploads/';
    if (strpos($relativePath, $prefix) !== 0) {
        return;
    }

    $filename = basename($relativePath);
    $absolutePath = __DIR__ . '/../uploads/' . $filename;
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }
}
