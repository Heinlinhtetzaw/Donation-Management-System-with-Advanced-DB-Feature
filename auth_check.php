<?php
require_once 'config.php';

// Check login
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_username'])) {
    header("Location: adlogin.php");
    exit();
}

// Session timeout (15 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) &&
    (time() - $_SESSION['LAST_ACTIVITY'] > 900)) {

    session_unset();
    session_destroy();
    header("Location: adlogin.php?timeout=1");
    exit();
}

$_SESSION['LAST_ACTIVITY'] = time();
?>
