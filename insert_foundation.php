<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/UploadService.php';

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

try {
    $imagePath = upload_image('image');
    $stmt = $conn->prepare('INSERT INTO foundations (image_path, fname, description, intro) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $imagePath, $fname, $description, $intro);
    if (!$stmt->execute()) {
        remove_uploaded_file($imagePath);
        throw new RuntimeException('Database insert failed: ' . $stmt->error);
    }
    $stmt->close();
    set_flash('success', 'Foundation added successfully.');
} catch (RuntimeException $exception) {
    error_log('Foundation upload failed: ' . $exception->getMessage());
    set_flash('error', 'The foundation could not be saved. Check the image and try again.');
}
$conn->close();
redirect_to('addfoundation.php');
