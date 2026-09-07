<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/UploadService.php';
require_once __DIR__ . '/app/AdminAuditService.php';

require_post_request('addfoundation.php');
require_valid_csrf('addfoundation.php');

$conn = getDBConnection();

$fname = trim($_POST["fname"] ?? '');
$description = trim($_POST["description"] ?? '');
$intro = trim($_POST["intro"] ?? '');

if ($fname === '' || $description === '' || $intro === '') {
    set_flash('error', 'All foundation fields are required.');
    redirect_to('addfoundation.php');
}

$imagePath = null;
$transactionStarted = false;
try {
    $imagePath = upload_image('image');
    $conn->begin_transaction();
    $transactionStarted = true;
    $admin = current_admin_identity($conn);
    $adminId = $admin['id'];
    $stmt = $conn->prepare('INSERT INTO foundations (created_by_admin_id, image_path, fname, description, intro) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issss', $adminId, $imagePath, $fname, $description, $intro);
    $stmt->execute();
    $foundationId = (int) $conn->insert_id;
    $stmt->close();
    write_admin_audit($conn, $admin, 'created', 'foundation', $foundationId, 'Created foundation: ' . $fname);
    $conn->commit();
    set_flash('success', 'Foundation added successfully.');
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    if ($imagePath !== null) {
        remove_uploaded_file($imagePath);
    }
    error_log('Foundation upload failed: ' . $exception->getMessage());
    set_flash('error', 'The foundation could not be saved. Check the image and try again.');
}
$conn->close();
redirect_to('addfoundation.php');
