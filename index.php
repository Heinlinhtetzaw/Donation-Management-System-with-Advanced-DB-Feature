<?php
require_once 'config.php';
require_once __DIR__ . '/app/partials.php';

$conn = getDBConnection();
$result = $conn->query('SELECT image_path, fname, description FROM foundations ORDER BY fname');
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
    <title>Charity Donation Management</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
    <?php render_public_navigation('home'); ?>

    <main class="hero">
        <div class="content-container">
            <div class="content">
                <h1>TOGETHER <br>WE CAN MAKE <br>A DIFFERENCE</h1>
                <p>To improve our environment</p>
            </div>
            <div class="content right-content">
                <h1>Help the Needy</h1>
                <p>This charitable donation is written because we need your help to provide the necessities of life in homes for underprivileged children, abandoned orphans, and elderly grandparents.</p>
            </div>
        </div>

        <h2 class="section-title">Our Partner Foundations</h2>
        <div class="charity-container">
            <?php if ($foundations === []): ?>
                <p>No foundations are available yet.</p>
            <?php else: ?>
                <?php foreach ($foundations as $foundation): ?>
                    <article class="charity-card">
                        <div class="image" style="background-image: url('<?= e(public_asset_path($foundation['image_path'])) ?>');" role="img" aria-label="<?= e($foundation['fname']) ?>"></div>
                        <div class="content">
                            <h3><?= e($foundation['fname']) ?></h3>
                            <p><?= e($foundation['description']) ?></p>
                            <a href="about.php" class="read-more">Read More</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php render_public_footer(); ?>
</body>
</html>
