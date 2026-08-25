<?php
require_once 'config.php';

require_admin();

// Session timeout (15 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) &&
    (time() - $_SESSION['LAST_ACTIVITY'] > 900)) {

    session_unset();
    session_destroy();
    redirect_to('adlogin.php?timeout=1');
}

$_SESSION['LAST_ACTIVITY'] = time();
?>
