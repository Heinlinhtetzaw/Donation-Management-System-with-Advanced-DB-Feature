<?php
require_once 'config.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/partials.php';

if (is_admin_authenticated()) {
    header("Location: addashboard.php");
    exit();
}

$errorMessage = '';
$successMessage = '';
$lockoutRemaining = null;

if (isset($_GET['timeout']) && $_GET['timeout'] === '1') {
    $errorMessage = "Session expired. Please log in again.";
}

if (isset($_GET['signup']) && $_GET['signup'] === '1') {
    $successMessage = "Signup successful. Please log in.";
}

if (!empty($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (!empty($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['lockout_remaining'])) {
    $lockoutRemaining = (int) $_SESSION['lockout_remaining'];
    if ($lockoutRemaining < 0) {
        $lockoutRemaining = 0;
    } elseif ($lockoutRemaining > 30) {
        $lockoutRemaining = 30;
    }
    unset($_SESSION['lockout_remaining']);
}

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/adlogin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php render_public_navigation('login'); ?>

    <div class="hero">
        <div class="login-container">
            <h2>Admin Login</h2>
            <?php if ($errorMessage !== ''): ?>
                <p class="error-message"
                   <?php if ($lockoutRemaining !== null): ?>
                       data-lockout-seconds="<?php echo (int) $lockoutRemaining; ?>"
                   <?php endif; ?>
                ><?= e($errorMessage) ?></p>
            <?php endif; ?>
            <?php if ($successMessage !== ''): ?>
                <p style="color: #28a745; font-size: 14px; margin-bottom: 10px;"><?= e($successMessage) ?></p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" maxlength="50" autocomplete="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" maxlength="128" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>

            <p style="margin-top: 12px; font-size: 14px;">
                No account? <a href="signup.php" style="color: orange;">Sign up</a>
            </p>
        </div>
    </div>
</body>
<script>
    (function () {
        var el = document.querySelector('.error-message[data-lockout-seconds]');
        if (!el) return;
        var remaining = parseInt(el.getAttribute('data-lockout-seconds'), 10);
        if (!Number.isFinite(remaining) || remaining <= 0) return;

        function render(seconds) {
            var mins = Math.floor(seconds / 60);
            var secs = seconds % 60;
            return 'Account locked. Try again in ' + mins + ' min ' + String(secs).padStart(2, '0') + ' sec.';
        }

        el.textContent = render(remaining);
        var timer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(timer);
                el.textContent = 'You can try logging in again now.';
                el.removeAttribute('data-lockout-seconds');
                return;
            }
            el.textContent = render(remaining);
        }, 1000);
    })();
</script>
</html>
