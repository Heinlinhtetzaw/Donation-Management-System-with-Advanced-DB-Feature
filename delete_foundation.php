<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/AdminAuditService.php';

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

    $conn->begin_transaction();
    try {
        $admin = current_admin_identity($conn);
        // Serialize deletion against donation submission, which takes a shared lock.
        $image = $conn->prepare('SELECT image_path, fname FROM foundations WHERE fid = ? FOR UPDATE');
        $image->bind_param('i', $fid);
        $image->execute();
        $record = $image->get_result()->fetch_assoc();
        $image->close();
        if (!$record) {
            throw new RuntimeException('Foundation not found.');
        }

        $usage = $conn->prepare('SELECT COUNT(*) AS count FROM donations WHERE foundation_id = ?');
        $usage->bind_param('i', $fid);
        $usage->execute();
        $donationCount = (int) $usage->get_result()->fetch_assoc()['count'];
        $usage->close();
        if ($donationCount > 0) {
            throw new DomainException('Foundation is in use.');
        }

        $stmt = $conn->prepare('DELETE FROM foundations WHERE fid = ?');
        $stmt->bind_param('i', $fid);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            throw new RuntimeException('Foundation not found.');
        }
        $stmt->close();
        write_admin_audit($conn, $admin, 'deleted', 'foundation', $fid, 'Deleted foundation: ' . $record['fname']);
        $conn->commit();

        remove_uploaded_file($record['image_path']);
        set_flash('success', 'Foundation deleted.');
    } catch (DomainException $exception) {
        $conn->rollback();
        set_flash('error', 'This foundation has donation records and cannot be deleted.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Foundation deletion failed: ' . $exception->getMessage());
        set_flash('error', 'The foundation could not be deleted.');
    }
} else {
    header("Location: addfoundation.php");
    exit();
}

$conn->close();

// Redirect back to the addfoundation.html page
redirect_to('addfoundation.php');
?>
