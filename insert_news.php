<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/UploadService.php';
require_once __DIR__ . '/app/AdminAuditService.php';

require_post_request('addnews.php');
require_valid_csrf('addnews.php');

$conn = getDBConnection();

$title = trim($_POST["title"] ?? '');
$content = trim($_POST["content"] ?? '');

if ($title === '' || $content === '') {
    set_flash('error', 'A title and content are required.');
    redirect_to('addnews.php');
}

$imagePath = null;
$transactionStarted = false;
try {
    $imagePath = upload_image('image');
    $conn->begin_transaction();
    $transactionStarted = true;
    $admin = current_admin_identity($conn);
    $adminId = $admin['id'];
    $stmt = $conn->prepare('INSERT INTO news (created_by_admin_id, title, content, image_path) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $adminId, $title, $content, $imagePath);
    $stmt->execute();
    $newsId = (int) $conn->insert_id;
    $stmt->close();
    write_admin_audit($conn, $admin, 'created', 'news', $newsId, 'Created news article: ' . $title);
    $conn->commit();
    set_flash('success', 'News article added successfully.');
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    if ($imagePath !== null) {
        remove_uploaded_file($imagePath);
    }
    error_log('News upload failed: ' . $exception->getMessage());
    set_flash('error', 'The news article could not be saved. Check the image and try again.');
}
$conn->close();
redirect_to('addnews.php');
