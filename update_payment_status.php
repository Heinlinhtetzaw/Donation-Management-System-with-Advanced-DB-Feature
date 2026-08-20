<?php
require_once 'auth_check.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adddonationstatus.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header("Location: adddonationstatus.php");
    exit();
}

// Database connection
$conn = getDBConnection();

// Get donation ID and action (complete or pending)
if (isset($_POST["id"]) && isset($_POST["action"])) {
    $id = (int) $_POST["id"];
    $action = $_POST["action"];

    // Validate the action
    if ($action != "complete" && $action != "pending") {
        die("Invalid action.");
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

    if ($stmt->execute()) {
        header("Location: adddonationstatus.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    header("Location: adddonationstatus.php");
    exit();
}

$conn->close();
?>
