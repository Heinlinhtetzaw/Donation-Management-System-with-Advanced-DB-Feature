<?php
require_once 'config.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adlogin.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: adlogin.php");
    exit();
}

$username = request_string($_POST, 'username');
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

if ($username === '' || text_length($username) > 50 || $password === '' || strlen($password) > 128) {
    $_SESSION['error'] = "Username and password are required.";
    header("Location: adlogin.php");
    exit();
}

$conn = getDBConnection();
$maxAttempts = 3;
$lockoutSeconds = 30;

$ip = get_client_ip();
$ipAttempts = load_login_attempts();
$ipRecord = isset($ipAttempts[$ip]) && is_array($ipAttempts[$ip]) ? $ipAttempts[$ip] : ['failed' => 0, 'last_failed' => 0];
$ipFailed = (int) ($ipRecord['failed'] ?? 0);
$ipLastFailed = (int) ($ipRecord['last_failed'] ?? 0);

if ($ipFailed >= $maxAttempts) {
    $elapsed = time() - $ipLastFailed;
    if ($elapsed < $lockoutSeconds) {
        $remaining = $lockoutSeconds - $elapsed;
        if ($remaining < 0) {
            $remaining = 0;
        } elseif ($remaining > $lockoutSeconds) {
            $remaining = $lockoutSeconds;
        }
        $remainingMinutes = (int) floor($remaining / 60);
        $remainingSeconds = (int) ($remaining % 60);
        $_SESSION['lockout_remaining'] = $remaining;
        $_SESSION['error'] = sprintf(
            "Account locked. Try again in %d min %02d sec.",
            $remainingMinutes,
            $remainingSeconds
        );
        header("Location: adlogin.php");
        exit();
    }

    $ipRecord = ['failed' => 0, 'last_failed' => 0];
    $ipAttempts[$ip] = $ipRecord;
    save_login_attempts($ipAttempts);
    $ipFailed = 0;
    $ipLastFailed = 0;
}
$stmt = $conn->prepare(
    "SELECT admin_id, adname, adpassword, failed_attempts, last_failed_login, " .
    "TIMESTAMPDIFF(SECOND, last_failed_login, NOW()) AS elapsed_seconds " .
    "FROM admin WHERE adname = ?"
);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $lastFailedLogin = !empty($admin['last_failed_login']) ? $admin['last_failed_login'] : null;
    $elapsedSeconds = isset($admin['elapsed_seconds']) ? (int) $admin['elapsed_seconds'] : 0;
    $failedAttempts = (int) $admin['failed_attempts'];

    if ($failedAttempts >= $maxAttempts) {
        if ($lastFailedLogin === null) {
            $stamp = $conn->prepare("UPDATE admin SET last_failed_login = NOW() WHERE adname = ?");
            $stamp->bind_param("s", $admin['adname']);
            $stamp->execute();
            $_SESSION['error'] = "Account locked. Try again later.";
            header("Location: adlogin.php");
            exit();
        }
        if ($elapsedSeconds < $lockoutSeconds) {
            $remaining = $lockoutSeconds - $elapsedSeconds;
            if ($remaining < 0) {
                $remaining = 0;
            } elseif ($remaining > $lockoutSeconds) {
                $remaining = $lockoutSeconds;
            }
            $remainingMinutes = (int) floor($remaining / 60);
            $remainingSeconds = (int) ($remaining % 60);
            $_SESSION['lockout_remaining'] = $remaining;
            $_SESSION['error'] = sprintf(
                "Account locked. Try again in %d min %02d sec.",
                $remainingMinutes,
                $remainingSeconds
            );
            header("Location: adlogin.php");
            exit();
        }

        $unlock = $conn->prepare("UPDATE admin SET failed_attempts = 0, last_failed_login = NULL WHERE adname = ?");
        $unlock->bind_param("s", $admin['adname']);
        $unlock->execute();
        $failedAttempts = 0;
    }

    $storedPassword = (string) $admin['adpassword'];
    $isHashedPassword = password_get_info($storedPassword)['algo'] !== null;
    $isValidPassword = $isHashedPassword && password_verify($password, $storedPassword);

    if ($isValidPassword) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['adname'];
        $_SESSION['LAST_ACTIVITY'] = time();

        $reset = $conn->prepare("UPDATE admin SET failed_attempts = 0, last_failed_login = NULL WHERE adname = ?");
        $reset->bind_param("s", $admin['adname']);
        $reset->execute();

        if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $rehash = $conn->prepare("UPDATE admin SET adpassword = ? WHERE adname = ?");
            $rehash->bind_param("ss", $newHash, $admin['adname']);
            $rehash->execute();
        }

        $ipAttempts[$ip] = ['failed' => 0, 'last_failed' => 0];
        save_login_attempts($ipAttempts);

        header("Location: addashboard.php");
        exit();
    }

    $failed = $failedAttempts + 1;
    $update = $conn->prepare("UPDATE admin SET failed_attempts = ?, last_failed_login = NOW() WHERE adname = ?");
    $update->bind_param("is", $failed, $admin['adname']);
    $update->execute();

    $ipFailed += 1;
    $ipAttempts[$ip] = ['failed' => $ipFailed, 'last_failed' => time()];
    save_login_attempts($ipAttempts);

    if ($failed >= $maxAttempts) {
        $remainingMinutes = (int) floor($lockoutSeconds / 60);
        $remainingSeconds = (int) ($lockoutSeconds % 60);
        $_SESSION['lockout_remaining'] = $lockoutSeconds;
        $_SESSION['error'] = sprintf(
            "Account locked. Try again in %d min %02d sec.",
            $remainingMinutes,
            $remainingSeconds
        );
        header("Location: adlogin.php");
        exit();
    }

    $_SESSION['error'] = "Invalid username or password.";
    header("Location: adlogin.php");
    exit();
}



$ipFailed += 1;
$ipAttempts[$ip] = ['failed' => $ipFailed, 'last_failed' => time()];
save_login_attempts($ipAttempts);

if ($ipFailed >= $maxAttempts) {
    $remainingMinutes = (int) floor($lockoutSeconds / 60);
    $remainingSeconds = (int) ($lockoutSeconds % 60);
    $_SESSION['lockout_remaining'] = $lockoutSeconds;
    $_SESSION['error'] = sprintf(
        "Account locked. Try again in %d min %02d sec.",
        $remainingMinutes,
        $remainingSeconds
    );
    header("Location: adlogin.php");
    exit();
}

$_SESSION['error'] = "Invalid username or password.";
header("Location: adlogin.php");
exit();
?>
