<?php
require_once 'auth_check.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: donor.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header("Location: donor.php");
    exit();
}

// Database connection
$conn = getDBConnection();

// Get the donation ID to delete
if (isset($_POST["id"])) {
    $fid = (int) $_POST["id"];

    // Delete the donation
    $sql = "DELETE FROM donations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fid);

    $stmt->execute();

    $stmt->close();
} else {
    header("Location: donor.php");
    exit();
}

$conn->close();

// Redirect back to the addfoundation.html page
header("Location: donor.php");
exit();
?>
