<?php
require_once 'auth_check.php';
require_once 'config.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/partials.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid request.";
        header("Location: admin_invite.php");
        exit();
    }

    try {
        $newCode = generate_admin_invite_code();
        store_admin_invite_record($newCode, false);
        $_SESSION['success'] = "New invite code generated.";
    } catch (Throwable $exception) {
        error_log('Invite-code generation failed: ' . $exception->getMessage());
        $_SESSION['error'] = "The invite code could not be generated.";
    }
    header("Location: admin_invite.php");
    exit();
}

$record = get_admin_invite_record();
$currentCode = $record['code'];
$isUsed = !empty($record['used']);

$errorMessage = '';
$successMessage = '';
if (!empty($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (!empty($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Invite Code</title>
    <link rel="stylesheet" href="css/addashb.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .invite-container {
            margin-left: 250px;
            padding: 20px;
        }
        .invite-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 520px;
        }
        .invite-code {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 10px 0 6px;
        }
        .invite-status {
            font-size: 14px;
            color: #555;
        }
        .invite-actions {
            margin-top: 16px;
        }
        .invite-actions button {
            padding: 8px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #2C3E50;
            color: #fff;
        }
        .invite-actions button:hover {
            background: #1e2a36;
        }
        .message-success {
            color: #28a745;
            margin-bottom: 10px;
        }
        .message-error {
            color: #e74c3c;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php render_admin_sidebar('invite'); ?>

        <div class="invite-container">
            <div class="invite-card">
                <h2>Admin Invite Code</h2>
                <?php if ($successMessage !== ''): ?>
                    <div class="message-success"><?= e($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="message-error"><?= e($errorMessage) ?></div>
                <?php endif; ?>

                <div class="invite-code">
                    <?= $currentCode !== '' ? e($currentCode) : 'No code generated yet' ?>
                </div>
                <div class="invite-status">
                    Status: <?php echo $currentCode === '' ? 'Not set' : ($isUsed ? 'Used' : 'Active'); ?>
                </div>

                <div class="invite-actions">
                    <form action="admin_invite.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <button type="submit">Generate New Code</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
