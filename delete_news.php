<?php
require_once 'auth_check.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: addnews.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header("Location: addnews.php");
    exit();
}

// Database connection
$conn = getDBConnection();

// Get the news ID to delete
if (isset($_POST["id"])) {
    $nid = (int) $_POST["id"];

    // Delete the news
    $sql = "DELETE FROM news WHERE nid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $nid);

    $stmt->execute();

    $stmt->close();
} else {
    header("Location: addnews.php");
    exit();
}

$conn->close();

// Redirect back to the addnews.html page
header("Location: addnews.php");
exit();
?>
