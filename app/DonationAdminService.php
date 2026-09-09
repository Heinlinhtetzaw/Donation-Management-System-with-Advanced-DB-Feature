<?php

require_once __DIR__ . '/AdminAuditService.php';

const DONATION_STATUSES = ['Pending', 'Complete'];
const DONATION_PAYMENT_METHODS = ['Cash', 'Wavepay', 'Kpay'];

function donation_reference_for_id($id) {
    return 'DON-' . str_pad((string) $id, 8, '0', STR_PAD_LEFT);
}

function donation_ledger_filters(array $query) {
    $status = request_string($query, 'status');
    $from = request_string($query, 'from');
    $to = request_string($query, 'to');

    return [
        'status' => in_array($status, DONATION_STATUSES, true) ? $status : '',
        'foundation_id' => positive_int($query['foundation_id'] ?? null),
        'search' => text_limit(request_string($query, 'q'), 120),
        'from' => is_iso_date($from) ? $from : '',
        'to' => is_iso_date($to) ? $to : '',
    ];
}

function donation_filter_clause(array $filters, array &$values, &$types) {
    $conditions = [];
    $values = [];
    $types = '';

    if ($filters['status'] !== '') {
        $conditions[] = 'd.payment_status = ?';
        $types .= 's';
        $values[] = $filters['status'];
    }
    if ($filters['foundation_id'] !== null) {
        $conditions[] = 'd.foundation_id = ?';
        $types .= 'i';
        $values[] = $filters['foundation_id'];
    }
    if ($filters['search'] !== '') {
        $conditions[] = '(d.reference_code LIKE ? OR d.donor_name LIKE ? OR d.phone LIKE ?)';
        $pattern = '%' . $filters['search'] . '%';
        $types .= 'sss';
        $values[] = $pattern;
        $values[] = $pattern;
        $values[] = $pattern;
    }
    if ($filters['from'] !== '') {
        $conditions[] = 'd.created_at >= ?';
        $types .= 's';
        $values[] = $filters['from'] . ' 00:00:00';
    }
    if ($filters['to'] !== '') {
        $conditions[] = 'd.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
        $types .= 's';
        $values[] = $filters['to'];
    }

    return $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
}

function write_donation_audit(mysqli $conn, array $admin, $action, $entityId, $details) {
    write_admin_audit($conn, $admin, $action, 'donation', $entityId, $details);
}

function ensure_donor(mysqli $conn, $name, $address, $phone) {
    $exact = $conn->prepare('SELECT donor_id FROM donors WHERE full_name = ? AND phone = ? LIMIT 1 FOR UPDATE');
    $exact->bind_param('ss', $name, $phone);
    $exact->execute();
    $record = $exact->get_result()->fetch_assoc();
    $exact->close();
    if ($record) {
        return (int) $record['donor_id'];
    }

    $conflict = $conn->prepare('SELECT donor_id FROM donors WHERE full_name = ? OR phone = ? LIMIT 1 FOR UPDATE');
    $conflict->bind_param('ss', $name, $phone);
    $conflict->execute();
    $conflictingRecord = $conflict->get_result()->fetch_assoc();
    $conflict->close();
    if ($conflictingRecord) {
        throw new DomainException('This donor name and phone number do not match the existing donor record.');
    }

    $create = $conn->prepare(
        'INSERT INTO donors (full_name, address, phone) VALUES (?, ?, ?) '
        . 'ON DUPLICATE KEY UPDATE donor_id = LAST_INSERT_ID(donor_id)'
    );
    $create->bind_param('sss', $name, $address, $phone);
    $create->execute();
    $donorId = (int) $conn->insert_id;
    $create->close();

    // A concurrent request may have won either unique key. Confirm it created
    // the same identity before linking the donation to that donor.
    $verify = $conn->prepare('SELECT 1 FROM donors WHERE donor_id = ? AND full_name = ? AND phone = ?');
    $verify->bind_param('iss', $donorId, $name, $phone);
    $verify->execute();
    $identityMatches = $verify->get_result()->num_rows === 1;
    $verify->close();
    if (!$identityMatches) {
        throw new DomainException('This donor name and phone number do not match the existing donor record.');
    }

    return $donorId;
}
