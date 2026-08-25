<?php

function upload_image($fieldName) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a valid image.');
    }

    $file = $_FILES[$fieldName];
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] <= 0 || $file['size'] > $maxSize) {
        throw new RuntimeException('Image files must be no larger than 2 MB.');
    }

    $imageInfo = getimagesize($file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if ($imageInfo === false || !isset($allowedMimes[$imageInfo['mime']])) {
        throw new RuntimeException('Only JPEG, PNG, GIF, and WebP images are allowed.');
    }

    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('The upload directory is not available.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$imageInfo['mime']];
    $destination = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The image could not be uploaded.');
    }

    return 'uploads/' . $filename;
}
