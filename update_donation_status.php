<?php
require_once 'auth_check.php'; require_once 'csrf.php'; require_once __DIR__ . '/app/DonationAdminService.php';
require_post_request('donation_ledger.php'); require_valid_csrf('donation_ledger.php');
$id = positive_int($_POST['id'] ?? null); $status = $_POST['status'] ?? ''; $note = trim($_POST['note'] ?? '');
$noteLength = function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note);
if ($id === null || !in_array($status, DONATION_STATUSES, true) || $noteLength > 500) { set_flash('error', 'Choose Pending or Complete. Notes cannot exceed 500 characters.'); redirect_to('donation_detail.php?id=' . (int)$id); }
$conn=getDBConnection(); $conn->begin_transaction();
try {
    $current=$conn->prepare('SELECT payment_status FROM donations WHERE id=? FOR UPDATE'); $current->bind_param('i',$id); $current->execute(); $record=$current->get_result()->fetch_assoc(); $current->close();
    if (!$record) throw new RuntimeException('Donation record not found.');
    $old=$record['payment_status']; $admin=current_admin_identity($conn);
    $adminUsername = $admin['username'];
    $adminId = $admin['id'];
    $verifiedBy = $status === 'Complete' ? $admin['username'] : null;
    $verifiedByAdminId = $status === 'Complete' ? $admin['id'] : null;
    $update=$conn->prepare("UPDATE donations SET payment_status=?, status_note=?, verified_at=CASE WHEN ?='Complete' THEN CURRENT_TIMESTAMP ELSE NULL END, verified_by=?, verified_by_admin_id=? WHERE id=?"); $update->bind_param('ssssii',$status,$note,$status,$verifiedBy,$verifiedByAdminId,$id); $update->execute(); $update->close();
    $history=$conn->prepare('INSERT INTO donation_status_history (donation_id,previous_status,new_status,note,changed_by,changed_by_admin_id) VALUES (?,?,?,?,?,?)'); $history->bind_param('issssi',$id,$old,$status,$note,$adminUsername,$adminId); $history->execute(); $history->close();
    write_donation_audit($conn,$admin,'status_changed',$id,$old . ' → ' . $status . ($note !== '' ? ': ' . $note : ''));
    $conn->commit(); set_flash('success','Donation status updated and recorded in its audit history.');
} catch(Throwable $e) { $conn->rollback(); error_log('Donation status update failed: '.$e->getMessage()); set_flash('error','The donation could not be updated.'); }
$conn->close(); redirect_to('donation_detail.php?id='.$id);
