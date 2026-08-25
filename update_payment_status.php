<?php
require_once 'auth_check.php';
require_once 'csrf.php';

require_post_request('adddonationstatus.php');
require_valid_csrf('adddonationstatus.php');

// Database connection
$conn = getDBConnection();

// Get donation ID and action (complete or pending)
if (isset($_POST["id"]) && isset($_POST["action"])) {
    $id = positive_int($_POST['id']);
    $action = $_POST["action"];

    // Validate the action
    if ($id === null || !in_array($action, ['complete', 'pending'], true)) {
        set_flash('error', 'Invalid donation status request.');
        redirect_to('adddonationstatus.php');
    }

    // Prepare the SQL query based on the action
    if ($action == "complete") {
        // Update status to "Complete"
        $sql = "UPDATE donations SET payment_status = 'Complete' WHERE id = ?";
    } elseif ($action == "pending") {
        // Update status to "Pending"
        $sql = "UPDATE donations SET payment_status = 'Pending' WHERE id = ?";
    }

    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows === 1) {
        set_flash('success', 'Donation status updated.');
    } else {
        error_log('Donation status update failed: ' . $stmt->error);
        set_flash('error', 'The donation was not found or its status could not be updated.');
    }

    $stmt->close();
} else {
    set_flash('error', 'Invalid donation status request.');
}

$conn->close();
redirect_to('adddonationstatus.php');
?>
