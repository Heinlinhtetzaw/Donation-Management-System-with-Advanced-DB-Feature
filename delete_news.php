<?php
require_once 'auth_check.php';
require_once 'csrf.php';

require_post_request('addnews.php');
require_valid_csrf('addnews.php');

// Database connection
$conn = getDBConnection();

// Get the news ID to delete
if (isset($_POST["id"])) {
    $nid = positive_int($_POST['id']);
    if ($nid === null) {
        set_flash('error', 'Invalid news article.');
        redirect_to('addnews.php');
    }

    $image = $conn->prepare('SELECT image_path FROM news WHERE nid = ?');
    $image->bind_param('i', $nid);
    $image->execute();
    $record = $image->get_result()->fetch_assoc();
    $image->close();

    // Delete the news
    $sql = "DELETE FROM news WHERE nid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $nid);

    if ($stmt->execute() && $stmt->affected_rows === 1) {
        if ($record) {
            remove_uploaded_file($record['image_path']);
        }
        set_flash('success', 'News article deleted.');
    } else {
        set_flash('error', 'News article was not found.');
    }

    $stmt->close();
} else {
    header("Location: addnews.php");
    exit();
}

$conn->close();

// Redirect back to the addnews.html page
redirect_to('addnews.php');
?>
