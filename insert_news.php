<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/UploadService.php';

require_post_request('addnews.php');
require_valid_csrf('addnews.php');

$conn = getDBConnection();

$title = trim($_POST["title"] ?? '');
$content = trim($_POST["content"] ?? '');

if ($title === '' || $content === '') {
    set_flash('error', 'A title and content are required.');
    redirect_to('addnews.php');
}

try {
    $imagePath = upload_image('image');
    $stmt = $conn->prepare('INSERT INTO news (title, content, image_path) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $title, $content, $imagePath);
    if (!$stmt->execute()) {
        remove_uploaded_file($imagePath);
        throw new RuntimeException('Database insert failed: ' . $stmt->error);
    }
    $stmt->close();
    set_flash('success', 'News article added successfully.');
} catch (RuntimeException $exception) {
    error_log('News upload failed: ' . $exception->getMessage());
    set_flash('error', 'The news article could not be saved. Check the image and try again.');
}
$conn->close();
redirect_to('addnews.php');
