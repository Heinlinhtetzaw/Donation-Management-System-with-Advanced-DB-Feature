<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/partials.php';

$id = positive_int($_GET['id'] ?? null);
if ($id === null) {
    set_flash('error', 'Invalid news article.');
    redirect_to('addnews.php');
}

$conn = getDBConnection();
$stmt = $conn->prepare('SELECT nid, image_path, title, content FROM news WHERE nid = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$news = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$news) {
    set_flash('error', 'News article not found.');
    redirect_to('addnews.php');
}
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit News</title>
    <link rel="stylesheet" href="css/addn.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php render_admin_sidebar('news'); ?>
        <main class="main-content">
            <div class="form-container">
                <a class="back-link" href="addnews.php"><i class="fas fa-arrow-left"></i> Back to news</a>
                <h2>Edit News</h2>
                <form action="update_news.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int) $news['nid'] ?>">
                    <label for="title">Title:</label>
                    <input type="text" name="title" id="title" value="<?= e($news['title']) ?>" maxlength="200" required>
                    <label for="content">Content:</label>
                    <textarea name="content" id="content" rows="8" maxlength="10000" required><?= e($news['content']) ?></textarea>
                    <label>Current image:</label>
                    <img class="current-image" src="<?= e(public_asset_path($news['image_path'])) ?>" alt="Current image for <?= e($news['title']) ?>">
                    <label for="image">Replace image (optional):</label>
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.gif,.webp">
                    <input type="submit" value="Save Changes">
                </form>
            </div>
        </main>
    </div>
    <footer><p>&copy; <?= date('Y') ?> Donation Hub. All Rights Reserved.</p></footer>
</body>
</html>
