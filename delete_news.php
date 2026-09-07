<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/AdminAuditService.php';

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

    $conn->begin_transaction();
    try {
        $admin = current_admin_identity($conn);
        $image = $conn->prepare('SELECT image_path, title FROM news WHERE nid = ? FOR UPDATE');
        $image->bind_param('i', $nid);
        $image->execute();
        $record = $image->get_result()->fetch_assoc();
        $image->close();
        if (!$record) {
            throw new RuntimeException('News article not found.');
        }

        $stmt = $conn->prepare('DELETE FROM news WHERE nid = ?');
        $stmt->bind_param('i', $nid);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            throw new RuntimeException('News article not found.');
        }
        $stmt->close();
        write_admin_audit($conn, $admin, 'deleted', 'news', $nid, 'Deleted news article: ' . $record['title']);
        $conn->commit();

        remove_uploaded_file($record['image_path']);
        set_flash('success', 'News article deleted.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('News deletion failed: ' . $exception->getMessage());
        set_flash('error', 'The news article could not be deleted.');
    }
} else {
    header("Location: addnews.php");
    exit();
}

$conn->close();

// Redirect back to the addnews.html page
redirect_to('addnews.php');
?>
