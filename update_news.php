<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/UploadService.php';
require_once __DIR__ . '/app/AdminAuditService.php';

require_post_request('addnews.php');
require_valid_csrf('addnews.php');

$id = positive_int($_POST['id'] ?? null);
$title = request_string($_POST, 'title');
$content = request_string($_POST, 'content');
if ($id === null || !has_text_length($title, 1, 200) || !has_text_length($content, 1, 10000)) {
    set_flash('error', 'Enter a title and article within the displayed length limits.');
    redirect_to('addnews.php');
}

$conn = getDBConnection();
$newImagePath = null;
try {
    $conn->begin_transaction();
    $admin = current_admin_identity($conn);
    $select = $conn->prepare('SELECT image_path, title FROM news WHERE nid = ? FOR UPDATE');
    $select->bind_param('i', $id);
    $select->execute();
    $news = $select->get_result()->fetch_assoc();
    $select->close();
    if (!$news) {
        throw new RuntimeException('News article not found.');
    }

    $imagePath = $news['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newImagePath = upload_image('image');
        $imagePath = $newImagePath;
    }

    $update = $conn->prepare('UPDATE news SET image_path = ?, title = ?, content = ?, create_at = create_at WHERE nid = ?');
    $update->bind_param('sssi', $imagePath, $title, $content, $id);
    $update->execute();
    $update->close();
    write_admin_audit($conn, $admin, 'updated', 'news', $id, 'Updated news article: ' . $news['title'] . ' → ' . $title);
    $conn->commit();

    if ($newImagePath !== null) {
        remove_uploaded_file($news['image_path']);
    }
    set_flash('success', 'News article updated successfully.');
} catch (Throwable $exception) {
    $conn->rollback();
    if ($newImagePath !== null) {
        remove_uploaded_file($newImagePath);
    }
    error_log('News update failed: ' . $exception->getMessage());
    set_flash('error', 'The news article could not be updated. Check the image and try again.');
}
$conn->close();
redirect_to('addnews.php');
