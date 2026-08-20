<?php
require_once 'auth_check.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: addfoundation.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header("Location: addfoundation.php");
    exit();
}

// Database connection
$conn = getDBConnection();

// Get the foundation ID to delete
if (isset($_POST["id"])) {
    $fid = (int) $_POST["id"];

    // Delete the foundation
    $sql = "DELETE FROM foundations WHERE fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fid);

    $stmt->execute();

    $stmt->close();
} else {
    header("Location: addfoundation.php");
    exit();
}

$conn->close();

// Redirect back to the addfoundation.html page
header("Location: addfoundation.php");
exit();
?>
