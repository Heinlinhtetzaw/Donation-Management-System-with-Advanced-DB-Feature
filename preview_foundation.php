<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';

$id = positive_int($_GET['id'] ?? null);
if ($id === null) {
    redirect_to('addfoundation.php');
}
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT f.image_path, f.fname, f.description, f.intro, COALESCE(a.adname, 'Legacy / unknown') AS created_by FROM foundations f LEFT JOIN admin a ON a.admin_id = f.created_by_admin_id WHERE f.fid = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$foundation = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
if (!$foundation) {
    set_flash('error', 'Foundation not found.');
    redirect_to('addfoundation.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?= e($foundation['fname']) ?></title>
    <link rel="stylesheet" href="css/addf.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php render_admin_sidebar('foundations'); ?>
        <main class="main-content">
            <article class="preview-card">
                <a class="back-link" href="addfoundation.php"><i class="fas fa-arrow-left"></i> Back to foundations</a>
                <h1><?= e($foundation['fname']) ?></h1>
                <p><strong>Created by</strong><br><?= e($foundation['created_by']) ?></p>
                <img class="preview-image" src="<?= e($foundation['image_path']) ?>" alt="<?= e($foundation['fname']) ?>">
                <p><strong>Description</strong><br><?= e($foundation['description']) ?></p>
                <p><strong>Introduction</strong><br><?= e($foundation['intro']) ?></p>
            </article>
        </main>
    </div>
    <footer><p>@2025 Donation Hub. All Rights Reserved.</p></footer>
</body>
</html>
