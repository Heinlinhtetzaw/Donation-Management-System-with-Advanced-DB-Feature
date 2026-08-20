<?php
require_once 'auth_check.php';
require_once 'config.php';
require_once 'csrf.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: addfoundation.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header("Location: addfoundation.php");
    exit();
}

$conn = getDBConnection();

if (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
    die("Invalid image upload.");
}

$maxSize = 2 * 1024 * 1024; // 2MB
if ($_FILES["image"]["size"] > $maxSize) {
    die("Image is too large.");
}

$tmpName = $_FILES["image"]["tmp_name"];
$imageInfo = getimagesize($tmpName);
if ($imageInfo === false) {
    die("Invalid image file.");
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($imageInfo['mime'], $allowedMimes, true)) {
    die("Unsupported image type.");
}

$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) {
    die("Unsupported image extension.");
}

$uploadDir = "uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$safeName = bin2hex(random_bytes(16)) . '.' . $ext;
$imagePath = $uploadDir . $safeName;

if (!move_uploaded_file($tmpName, $imagePath)) {
    die("Error uploading image.");
}

$fname = trim($_POST["fname"] ?? '');
$description = trim($_POST["description"] ?? '');
$intro = trim($_POST["intro"] ?? '');

if ($fname === '' || $description === '' || $intro === '') {
    die("Invalid foundation details.");
}

$sql = "INSERT INTO foundations (image_path, fname, description, intro) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $imagePath, $fname, $description, $intro);

if ($stmt->execute()) {
    header("Location: addfoundation.php");
    exit();
}

echo "Error: " . $stmt->error;
