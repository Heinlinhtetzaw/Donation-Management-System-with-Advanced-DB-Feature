<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/partials.php';

$id = positive_int($_GET['id'] ?? null);
if ($id === null) {
    set_flash('error', 'Invalid foundation.');
    redirect_to('addfoundation.php');
}

$conn = getDBConnection();
$stmt = $conn->prepare('SELECT fid, image_path, fname, description, intro FROM foundations WHERE fid = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$foundation = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$foundation) {
    set_flash('error', 'Foundation not found.');
    redirect_to('addfoundation.php');
}
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Foundation</title>
    <link rel="stylesheet" href="css/addf.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php render_admin_sidebar('foundations'); ?>
        <main class="main-content">
            <div class="form-container">
                <a class="back-link" href="addfoundation.php"><i class="fas fa-arrow-left"></i> Back to foundations</a>
                <h2>Edit Foundation</h2>
                <form action="update_foundation.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int) $foundation['fid'] ?>">
                    <label>Current image:</label>
                    <img class="current-image" src="<?= e(public_asset_path($foundation['image_path'])) ?>" alt="Current image for <?= e($foundation['fname']) ?>">
                    <label for="image">Replace image (optional):</label>
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.gif,.webp">
                    <label for="fname">Foundation name:</label>
                    <textarea name="fname" id="fname" rows="4" maxlength="150" required><?= e($foundation['fname']) ?></textarea>
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="4" maxlength="1000" required><?= e($foundation['description']) ?></textarea>
                    <label for="intro">Introduction:</label>
                    <textarea name="intro" id="intro" rows="4" maxlength="5000" required><?= e($foundation['intro']) ?></textarea>
                    <input type="submit" value="Save Changes">
                </form>
            </div>
        </main>
    </div>
    <footer><p>&copy; <?= date('Y') ?> Donation Hub. All Rights Reserved.</p></footer>
</body>
</html>
