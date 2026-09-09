<?php
require_once 'config.php';
require_once __DIR__ . '/app/partials.php';

$conn = getDBConnection();
$result = $conn->query('SELECT fname, image_path, intro FROM foundations ORDER BY fname');
$foundations = [];
while ($foundation = $result->fetch_assoc()) {
    $foundations[] = $foundation;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Our Foundations</title>
    <link rel="stylesheet" href="css/about.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
    <?php render_public_navigation('about'); ?>

    <main class="hero">
        <div class="charity-container">
            <?php if ($foundations === []): ?>
                <p>No foundations are available yet.</p>
            <?php else: ?>
                <?php foreach ($foundations as $foundation): ?>
                    <article class="charity-card">
                        <div class="content">
                            <h2><?= e($foundation['fname']) ?></h2>
                            <p><?= e($foundation['intro']) ?></p>
                            <a href="donate.php" class="donate-btn">Donate</a>
                        </div>
                        <div class="image">
                            <img src="<?= e(public_asset_path($foundation['image_path'])) ?>" alt="<?= e($foundation['fname']) ?>">
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php render_public_footer(); ?>
</body>
</html>
