<?php
require_once 'config.php';
require_once __DIR__ . '/app/partials.php';

$conn = getDBConnection();
$result = $conn->query('SELECT nid, title, content, image_path, create_at FROM news ORDER BY create_at DESC, nid DESC');
$articles = [];
while ($article = $result->fetch_assoc()) {
    $articles[] = $article;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charity News</title>
    <link rel="stylesheet" href="css/news.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
    <?php render_public_navigation('news'); ?>

    <main class="hero">
        <div class="news-container">
            <?php if ($articles === []): ?>
                <p>No news is available yet.</p>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <?php $detailsId = 'news-details-' . (int) $article['nid']; ?>
                    <article class="news-card">
                        <div class="image" style="background-image: url('<?= e(public_asset_path($article['image_path'])) ?>');" role="img" aria-label="<?= e($article['title']) ?>"></div>
                        <div class="content">
                            <h3><?= e($article['title']) ?></h3>
                            <div class="date"><?= e($article['create_at']) ?></div>
                            <p><?= e(text_excerpt($article['content'], 150)) ?></p>
                            <div class="read-more-content" id="<?= e($detailsId) ?>">
                                <p><?= e($article['content']) ?></p>
                                <div class="buttons">
                                    <a href="donate.php" class="donate-btn">Donate</a>
                                </div>
                            </div>
                            <button type="button" class="read-more-btn" aria-expanded="false" aria-controls="<?= e($detailsId) ?>">Read More</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php render_public_footer(); ?>
    <script src="js/news.js"></script>
</body>
</html>
