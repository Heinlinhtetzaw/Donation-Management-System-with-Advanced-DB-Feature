<?php
require_once 'config.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/partials.php';

if (is_admin_authenticated()) {
    redirect_to('addashboard.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request.';
        redirect_to('signup.php');
    }

    $username = request_string($_POST, 'username');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $inviteCode = request_string($_POST, 'invite_code');

    if (preg_match('/^[\p{L}\p{N}_.-]{3,50}$/u', $username) !== 1) {
        $_SESSION['error'] = 'Username must be 3–50 letters, numbers, dots, dashes, or underscores.';
        redirect_to('signup.php');
    }
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = 'Passwords do not match.';
        redirect_to('signup.php');
    }

    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d])\S{8,128}$/';
    if (preg_match($passwordPattern, $password) !== 1) {
        $_SESSION['error'] = 'Password must be 8–128 characters and include uppercase, lowercase, number, and symbol (no spaces).';
        redirect_to('signup.php');
    }

    $conn = getDBConnection();
    $conn->begin_transaction();
    $isFirstAdmin = false;

    try {
        $firstAdmin = $conn->query(
            'SELECT admin_id FROM admin ORDER BY admin_id ASC LIMIT 1 FOR UPDATE'
        )->fetch_assoc();
        $isFirstAdmin = $firstAdmin === null;

        $check = $conn->prepare('SELECT 1 FROM admin WHERE adname = ? LIMIT 1');
        $check->bind_param('s', $username);
        $check->execute();
        $usernameExists = $check->get_result()->num_rows > 0;
        $check->close();
        if ($usernameExists) {
            throw new DomainException('Username already exists.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $createAdmin = static function () use ($conn, $username, $hashedPassword) {
            $insert = $conn->prepare(
                'INSERT INTO admin (adname, adpassword, failed_attempts) VALUES (?, ?, 0)'
            );
            $insert->bind_param('ss', $username, $hashedPassword);
            $insert->execute();
            $insert->close();
        };

        if ($isFirstAdmin) {
            $createAdmin();
        } else {
            consume_admin_invite_code($inviteCode, $createAdmin);
        }

        $conn->commit();
    } catch (DomainException $exception) {
        $conn->rollback();
        $conn->close();
        $_SESSION['error'] = $exception->getMessage();
        redirect_to('signup.php');
    } catch (Throwable $exception) {
        $conn->rollback();
        $conn->close();
        error_log('Admin signup failed: ' . $exception->getMessage());
        $_SESSION['error'] = 'Signup failed. Please try again.';
        redirect_to('signup.php');
    }

    $conn->close();
    if ($isFirstAdmin) {
        try {
            $inviteRecord = get_admin_invite_record();
            $newInviteCode = $inviteRecord['code'];
            if ($newInviteCode === '' || !empty($inviteRecord['used'])) {
                $newInviteCode = generate_admin_invite_code();
                store_admin_invite_record($newInviteCode, false);
            }
            $_SESSION['success'] = 'Signup successful. Invite code: ' . $newInviteCode;
        } catch (Throwable $exception) {
            error_log('Initial invite-code generation failed: ' . $exception->getMessage());
            $_SESSION['success'] = 'Signup successful. Log in to generate an invite code.';
        }
    } else {
        $_SESSION['success'] = 'Signup successful. Please log in.';
    }
    redirect_to('adlogin.php');
}

$errorMessage = '';
if (!empty($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

$conn = getDBConnection();
$requiresInviteCode = (int) $conn->query('SELECT COUNT(*) AS total FROM admin')->fetch_assoc()['total'] > 0;
$conn->close();
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup</title>
    <link rel="stylesheet" href="css/adlogin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php render_public_navigation('login'); ?>
    <main class="hero">
        <div class="login-container">
            <h2>Admin Signup</h2>
            <?php if ($errorMessage !== ''): ?>
                <p class="error-message"><?= e($errorMessage) ?></p>
            <?php endif; ?>
            <?php if (!$requiresInviteCode): ?>
                <p>Create the first administrator account. No invite code is required.</p>
            <?php endif; ?>

            <form action="signup.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" minlength="3" maxlength="50" autocomplete="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" required
                        pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d])\S{8,128}"
                        title="8–128 characters with uppercase, lowercase, number, and symbol; no spaces.">
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" maxlength="128" autocomplete="new-password" required>
                </div>
                <?php if ($requiresInviteCode): ?>
                    <div class="input-group">
                        <label for="invite_code">Invite Code</label>
                        <input type="password" id="invite_code" name="invite_code" maxlength="128" required>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn">Create Account</button>
            </form>

            <p style="margin-top: 12px; font-size: 14px;">
                Already have an account? <a href="adlogin.php" style="color: orange;">Login</a>
            </p>
        </div>
    </main>
</body>
</html>
