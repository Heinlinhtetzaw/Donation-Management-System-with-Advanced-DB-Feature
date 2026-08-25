<?php

const DONATION_STATUSES = ['Pending', 'Under Review', 'Complete', 'Rejected', 'Cancelled'];

function donation_reference_for_id($id) {
    return 'DON-' . str_pad((string) $id, 8, '0', STR_PAD_LEFT);
}

function write_donation_audit(mysqli $conn, $admin, $action, $entityId, $details) {
    $stmt = $conn->prepare('INSERT INTO admin_audit_logs (admin_username, action_type, entity_type, entity_id, details) VALUES (?, ?, \'donation\', ?, ?)');
    $entityId = (string) $entityId;
    $stmt->bind_param('ssss', $admin, $action, $entityId, $details);
    $stmt->execute();
    $stmt->close();
}

function ensure_donor(mysqli $conn, $name, $address, $phone) {
    $existing = $conn->prepare('SELECT donor_id FROM donors WHERE phone = ?');
    $existing->bind_param('s', $phone);
    $existing->execute();
    $record = $existing->get_result()->fetch_assoc();
    $existing->close();

    if ($record) {
        $donorId = (int) $record['donor_id'];
        $update = $conn->prepare('UPDATE donors SET full_name = ?, address = ? WHERE donor_id = ?');
        $update->bind_param('ssi', $name, $address, $donorId);
        $update->execute();
        $update->close();
        return $donorId;
    }

    $create = $conn->prepare('INSERT INTO donors (full_name, address, phone) VALUES (?, ?, ?)');
    $create->bind_param('sss', $name, $address, $phone);
    $create->execute();
    $donorId = (int) $conn->insert_id;
    $create->close();
    return $donorId;
}
