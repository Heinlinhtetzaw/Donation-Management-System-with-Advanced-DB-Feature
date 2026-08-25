<?php

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
