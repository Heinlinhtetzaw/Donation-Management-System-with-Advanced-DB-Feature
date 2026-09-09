<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/UploadService.php';
require_once __DIR__ . '/app/AdminAuditService.php';

require_post_request('addnews.php');
require_valid_csrf('addnews.php');

$title = request_string($_POST, 'title');
$content = request_string($_POST, 'content');

if (!has_text_length($title, 1, 200) || !has_text_length($content, 1, 10000)) {
    set_flash('error', 'Enter a title and article within the displayed length limits.');
    redirect_to('addnews.php');
}

$conn = getDBConnection();
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
