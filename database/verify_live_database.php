<?php
// Run from the project root: php database/verify_live_database.php
// This script rolls back every test write before it exits.

require_once __DIR__ . '/../config.php';

function fail_test($message) {
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

function assert_test($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$requiredColumns = [
    'admin' => ['adname', 'adpassword', 'failed_attempts', 'last_failed_login'],
    'foundations' => ['fid', 'image_path', 'fname', 'description', 'intro'],
    'news' => ['nid', 'image_path', 'title', 'content', 'create_at'],
    'donors' => ['donor_id', 'full_name', 'address', 'phone', 'created_at'],
    'donations' => ['id', 'donor_id', 'reference_code', 'donor_name', 'address', 'phone', 'amount', 'foundation_id', 'payment_method', 'payment_status', 'verified_at', 'verified_by', 'status_note', 'created_at'],
    'donation_status_history' => ['history_id', 'donation_id', 'previous_status', 'new_status', 'note', 'changed_by', 'changed_at'],
    'admin_audit_logs' => ['audit_id', 'admin_username', 'action_type', 'entity_type', 'entity_id', 'details', 'created_at'],
];

$conn = null;
try {
    $conn = getDBConnection();
    $conn->begin_transaction();

    foreach ($requiredColumns as $table => $columns) {
        $result = $conn->query("SHOW COLUMNS FROM {$table}");
        $actualColumns = [];
        while ($column = $result->fetch_assoc()) {
            $actualColumns[] = strtolower($column['Field']);
        }
        foreach ($columns as $column) {
            assert_test(in_array(strtolower($column), $actualColumns, true), "Column '{$table}.{$column}' is missing.");
        }
        echo "PASS: {$table} schema" . PHP_EOL;
    }

    $foundationResult = $conn->query('SELECT fid FROM foundations ORDER BY fid ASC LIMIT 1');
    $foundation = $foundationResult->fetch_assoc();
    assert_test($foundation !== null, 'At least one foundation is required for the donation flow test.');
    $foundationId = (int) $foundation['fid'];

    $name = '__database_flow_test__';
    $address = 'Transactional test record';
    $phone = '09123456789';
    $amount = '1000.00';
    $method = 'Cash';
    $pending = 'Pending';
    $insert = $conn->prepare('INSERT INTO donations (donor_name, address, phone, amount, foundation_id, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insert->bind_param('ssssiss', $name, $address, $phone, $amount, $foundationId, $method, $pending);
    $insert->execute();
    $donationId = (int) $conn->insert_id;
    $insert->close();
    assert_test($donationId > 0, 'Could not insert transactional test donation.');
    echo "PASS: donation created" . PHP_EOL;

    $complete = 'Complete';
    $update = $conn->prepare('UPDATE donations SET payment_status = ? WHERE id = ?');
    $update->bind_param('si', $complete, $donationId);
    $update->execute();
    assert_test($update->affected_rows === 1, 'Could not update transactional donation status.');
    $update->close();

    $status = $conn->query("SELECT payment_status FROM donations WHERE id = {$donationId}")->fetch_assoc();
    assert_test($status !== null && $status['payment_status'] === 'Complete', 'Updated donation status was not persisted.');
    echo "PASS: donation status update" . PHP_EOL;

    $conn->rollback();
    $conn->close();
    echo 'PASS: all database flow checks passed; every test write was rolled back.' . PHP_EOL;
} catch (Throwable $error) {
    if ($conn instanceof mysqli) {
        try {
            $conn->rollback();
            $conn->close();
        } catch (Throwable $cleanupError) {
            error_log('Database verification cleanup failed: ' . $cleanupError->getMessage());
        }
    }
    fail_test($error->getMessage());
}
