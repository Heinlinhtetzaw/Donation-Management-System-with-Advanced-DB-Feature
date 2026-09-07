<?php
require_once 'config.php';
require_once 'csrf.php';

if (is_admin_authenticated()) {
    header("Location: addashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid request.";
        header("Location: signup.php");
        exit();
    }

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $inviteCode = $_POST['invite_code'] ?? '';

    if ($username === '' || $password === '' || $confirmPassword === '') {
        $_SESSION['error'] = "All fields are required.";
        header("Location: signup.php");
        exit();
    }

    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: signup.php");
        exit();
    }

    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z\\d])\\S{8,}$/';
    if (!preg_match($passwordPattern, $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters and include uppercase, lowercase, number, and symbol (no spaces).";
        header("Location: signup.php");
        exit();
    }

    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM admin");
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $row = $countResult ? $countResult->fetch_assoc() : ['total' => 0];
        $countStmt->close();
        $isFirstAdmin = ((int) $row['total']) === 0;

        $check = $conn->prepare("SELECT 1 FROM admin WHERE adname = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $existing = $check->get_result();
        $usernameExists = $existing->num_rows > 0;
        $check->close();
        if ($usernameExists) {
            throw new DomainException('Username already exists.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $createAdmin = function () use ($conn, $username, $hashedPassword) {
            $insert = $conn->prepare("INSERT INTO admin (adname, adpassword, failed_attempts) VALUES (?, ?, 0)");
            $insert->bind_param("ss", $username, $hashedPassword);
            $insert->execute();
            $insert->close();
        };

        if ($isFirstAdmin) {
            $createAdmin();
        } else {
            // Validation, account creation, and marking the code used occur while
            // one exclusive file lock is held, preventing two uses of one code.
            consume_admin_invite_code($inviteCode, $createAdmin);
        }

        $conn->commit();
    } catch (DomainException $exception) {
        $conn->rollback();
        $conn->close();
        $_SESSION['error'] = $exception->getMessage();
        header("Location: signup.php");
        exit();
    } catch (Throwable $exception) {
        $conn->rollback();
        $conn->close();
        error_log('Admin signup failed: ' . $exception->getMessage());
        $_SESSION['error'] = "Signup failed. Please try again.";
        header("Location: signup.php");
        exit();
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
            $_SESSION['success'] = "Signup successful. Invite code: " . $newInviteCode;
        } catch (Throwable $exception) {
            error_log('Initial invite-code generation failed: ' . $exception->getMessage());
            $_SESSION['success'] = "Signup successful. Log in to generate an invite code.";
        }
    } else {
        $_SESSION['success'] = "Signup successful. Please log in.";
    }
    header("Location: adlogin.php");
    exit();
}

$errorMessage = '';
if (!empty($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

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
    <nav class="navbar">
        <div class="logo"><image src="./image/logooo2.jpg">
            <span>Charity Donation Management</span>
        </div>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i>Home</a></li>
            <li><a href="about.php"><i class="fas fa-building"></i>About</a></li>
            <li><a href="news.php"><i class="fas fa-newspaper"></i>News</a></li>
            <li><a href="donate.php"><i class="fas fa-donate"></i>Donate</a></li>
            <li class="active"><a href="adlogin.php" class="btn admin"><i class="fas fa-sign-in-alt"></i>Login</a></li>
        </ul>
    </nav>

    <div class="hero">
        <div class="login-container">
            <h2>Admin Signup</h2>
            <?php if ($errorMessage !== ''): ?>
                <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <form action="signup.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                        pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d])\S{8,}"
                        title="At least 8 characters with uppercase, lowercase, number, and symbol; no spaces.">
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="input-group">
                    <label for="invite_code">Invite Code</label>
                    <input type="password" id="invite_code" name="invite_code" required>
                </div>
                <button type="submit" class="btn">Create Account</button>
            </form>

            <p style="margin-top: 12px; font-size: 14px;">
                Already have an account? <a href="adlogin.php" style="color: orange;">Login</a>
            </p>
        </div>
    </div>
</body>
</html>
