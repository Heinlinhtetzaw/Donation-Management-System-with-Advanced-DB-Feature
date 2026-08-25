<?php
require_once 'auth_check.php';
require_once 'csrf.php';

require_post_request('addfoundation.php');
require_valid_csrf('addfoundation.php');

// Database connection
$conn = getDBConnection();

// Get the foundation ID to delete
if (isset($_POST["id"])) {
    $fid = positive_int($_POST['id']);
    if ($fid === null) {
        set_flash('error', 'Invalid foundation.');
        redirect_to('addfoundation.php');
    }

    $usage = $conn->prepare('SELECT COUNT(*) AS count FROM donations WHERE foundation_id = ?');
    $usage->bind_param('i', $fid);
    $usage->execute();
    $donationCount = (int) $usage->get_result()->fetch_assoc()['count'];
    $usage->close();
    if ($donationCount > 0) {
        set_flash('error', 'This foundation has donation records and cannot be deleted.');
        $conn->close();
        redirect_to('addfoundation.php');
    }

    $image = $conn->prepare('SELECT image_path FROM foundations WHERE fid = ?');
    $image->bind_param('i', $fid);
    $image->execute();
    $record = $image->get_result()->fetch_assoc();
    $image->close();

    // Delete the foundation
    $sql = "DELETE FROM foundations WHERE fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fid);

    if ($stmt->execute() && $stmt->affected_rows === 1) {
        if ($record) {
            remove_uploaded_file($record['image_path']);
        }
        set_flash('success', 'Foundation deleted.');
    } else {
        set_flash('error', 'Foundation was not found.');
    }

    $stmt->close();
} else {
    header("Location: addfoundation.php");
    exit();
}

$conn->close();

// Redirect back to the addfoundation.html page
redirect_to('addfoundation.php');
?>
