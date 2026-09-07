<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';

$id = positive_int($_GET['id'] ?? null);
if ($id === null) {
    redirect_to('addnews.php');
}
$conn = getDBConnection();
$stmt = $conn->prepare('SELECT image_path, title, content, create_at FROM news WHERE nid = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$news = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
if (!$news) {
    set_flash('error', 'News article not found.');
    redirect_to('addnews.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?= e($news['title']) ?></title>
    <link rel="stylesheet" href="css/addn.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php render_admin_sidebar('news'); ?>
        <main class="main-content">
            <article class="preview-card">
                <a class="back-link" href="addnews.php"><i class="fas fa-arrow-left"></i> Back to news</a>
                <h1><?= e($news['title']) ?></h1>
                <p class="preview-date"><?= e($news['create_at']) ?></p>
                <img class="preview-image" src="<?= e($news['image_path']) ?>" alt="<?= e($news['title']) ?>">
                <p><?= e($news['content']) ?></p>
            </article>
        </main>
    </div>
    <footer><p>@2025 Donation Hub. All Rights Reserved.</p></footer>
</body>
</html>
