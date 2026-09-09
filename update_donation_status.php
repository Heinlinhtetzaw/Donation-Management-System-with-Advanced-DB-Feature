<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/DonationAdminService.php';

require_post_request('donation_ledger.php');
require_valid_csrf('donation_ledger.php');

$donationId = positive_int($_POST['id'] ?? null);
$status = request_string($_POST, 'status');
$note = request_string($_POST, 'note');
$fallback = $donationId === null
    ? 'donation_ledger.php'
    : 'donation_detail.php?id=' . $donationId;

if ($donationId === null || !in_array($status, DONATION_STATUSES, true) || text_length($note) > 500) {
    set_flash('error', 'Choose Pending or Complete. Notes cannot exceed 500 characters.');
    redirect_to($fallback);
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    $current = $conn->prepare('SELECT payment_status FROM donations WHERE id = ? FOR UPDATE');
    $current->bind_param('i', $donationId);
    $current->execute();
    $record = $current->get_result()->fetch_assoc();
    $current->close();
    if (!$record) {
        throw new DomainException('Donation record not found.');
    }

    $previousStatus = $record['payment_status'];
    $admin = current_admin_identity($conn);
    $adminUsername = $admin['username'];
    $adminId = $admin['id'];
    $verifiedBy = $status === 'Complete' ? $adminUsername : null;
    $verifiedByAdminId = $status === 'Complete' ? $adminId : null;

    $update = $conn->prepare(
        'UPDATE donations SET payment_status = ?, status_note = ?, '
        . "verified_at = CASE WHEN ? = 'Complete' THEN CURRENT_TIMESTAMP ELSE NULL END, "
        . 'verified_by = ?, verified_by_admin_id = ?, created_at = created_at WHERE id = ?'
    );
    $update->bind_param(
        'ssssii',
        $status,
        $note,
        $status,
        $verifiedBy,
        $verifiedByAdminId,
        $donationId
    );
    $update->execute();
    $update->close();

    $history = $conn->prepare(
        'INSERT INTO donation_status_history '
        . '(donation_id, previous_status, new_status, note, changed_by, changed_by_admin_id) '
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );
    $history->bind_param('issssi', $donationId, $previousStatus, $status, $note, $adminUsername, $adminId);
    $history->execute();
    $history->close();

    $auditDetails = $previousStatus . ' → ' . $status;
    if ($note !== '') {
        $auditDetails .= ': ' . $note;
    }
    write_donation_audit($conn, $admin, 'status_changed', $donationId, $auditDetails);

    $conn->commit();
    set_flash('success', 'Donation status updated and recorded in its audit history.');
} catch (DomainException $exception) {
    $conn->rollback();
    set_flash('error', $exception->getMessage());
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('Donation status update failed: ' . $exception->getMessage());
    set_flash('error', 'The donation could not be updated.');
}

$conn->close();
redirect_to($fallback);
