<?php
require_once 'auth_check.php';
require_once 'csrf.php';

require_post_request('donor.php');
require_valid_csrf('donor.php');

// Database connection
$conn = getDBConnection();

// Get the donation ID to delete
if (isset($_POST["id"])) {
    $fid = positive_int($_POST['id']);
    if ($fid === null) {
        set_flash('error', 'Invalid donation.');
        redirect_to('donor.php');
    }

    // Delete the donation
    $sql = "DELETE FROM donations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fid);

    if ($stmt->execute() && $stmt->affected_rows === 1) {
        set_flash('success', 'Donation record deleted.');
    } else {
        set_flash('error', 'Donation record was not found.');
    }

    $stmt->close();
} else {
    header("Location: donor.php");
    exit();
}

$conn->close();

// Redirect back to the addfoundation.html page
redirect_to('donor.php');
?>
