<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/UploadService.php';

require_post_request('addfoundation.php');
require_valid_csrf('addfoundation.php');

$id = positive_int($_POST['id'] ?? null);
$fname = trim($_POST['fname'] ?? '');
$description = trim($_POST['description'] ?? '');
$intro = trim($_POST['intro'] ?? '');
if ($id === null || $fname === '' || $description === '' || $intro === '') {
    set_flash('error', 'All foundation fields are required.');
    redirect_to('addfoundation.php');
}

$conn = getDBConnection();
$newImagePath = null;
try {
    $conn->begin_transaction();
    $select = $conn->prepare('SELECT image_path FROM foundations WHERE fid = ? FOR UPDATE');
    $select->bind_param('i', $id);
    $select->execute();
    $foundation = $select->get_result()->fetch_assoc();
    $select->close();
    if (!$foundation) {
        throw new RuntimeException('Foundation not found.');
    }

    $imagePath = $foundation['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newImagePath = upload_image('image');
        $imagePath = $newImagePath;
    }

    $update = $conn->prepare('UPDATE foundations SET image_path = ?, fname = ?, description = ?, intro = ? WHERE fid = ?');
    $update->bind_param('ssssi', $imagePath, $fname, $description, $intro, $id);
    $update->execute();
    $update->close();
    $conn->commit();

    if ($newImagePath !== null) {
        remove_uploaded_file($foundation['image_path']);
    }
    set_flash('success', 'Foundation updated successfully.');
} catch (Throwable $exception) {
    $conn->rollback();
    if ($newImagePath !== null) {
        remove_uploaded_file($newImagePath);
    }
    error_log('Foundation update failed: ' . $exception->getMessage());
    set_flash('error', 'The foundation could not be updated. Check the image and try again.');
}
$conn->close();
redirect_to('addfoundation.php');
