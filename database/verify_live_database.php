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
        fail_test($message);
    }
}

$requiredColumns = [
    'admin' => ['adname', 'adpassword', 'failed_attempts', 'last_failed_login'],
    'foundations' => ['fid', 'image_path', 'fname', 'description', 'intro'],
    'news' => ['nid', 'image_path', 'title', 'content', 'create_at'],
    'donations' => ['id', 'donor_name', 'address', 'phone', 'amount', 'foundation_id', 'payment_method', 'payment_status', 'created_at'],
];

$conn = getDBConnection();
$conn->begin_transaction();

try {
    foreach ($requiredColumns as $table => $columns) {
        $result = $conn->query("SHOW COLUMNS FROM {$table}");
        assert_test($result !== false, "Table '{$table}' is missing.");

        $actualColumns = [];
        while ($column = $result->fetch_assoc()) {
            $actualColumns[] = $column['Field'];
        }
        echo "INFO: {$table} columns: " . implode(', ', $actualColumns) . PHP_EOL;
        $normalizedColumns = array_map('strtolower', $actualColumns);
        foreach ($columns as $column) {
            assert_test(in_array(strtolower($column), $normalizedColumns, true), "Column '{$table}.{$column}' is missing.");
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
    assert_test($insert->execute(), 'Could not insert transactional test donation.');
    $donationId = $conn->insert_id;
    $insert->close();
    echo "PASS: donation created" . PHP_EOL;

    $complete = 'Complete';
    $update = $conn->prepare('UPDATE donations SET payment_status = ? WHERE id = ?');
    $update->bind_param('si', $complete, $donationId);
    assert_test($update->execute() && $update->affected_rows === 1, 'Could not update transactional donation status.');
    $update->close();

    $status = $conn->query("SELECT payment_status FROM donations WHERE id = {$donationId}")->fetch_assoc();
    assert_test($status !== null && $status['payment_status'] === 'Complete', 'Updated donation status was not persisted.');
    echo "PASS: donation status update" . PHP_EOL;

    $delete = $conn->prepare('DELETE FROM donations WHERE id = ?');
    $delete->bind_param('i', $donationId);
    assert_test($delete->execute() && $delete->affected_rows === 1, 'Could not delete transactional test donation.');
    $delete->close();
    echo "PASS: donation deletion" . PHP_EOL;

    $conn->rollback();
    $conn->close();
    echo 'PASS: all database flow checks passed; every test write was rolled back.' . PHP_EOL;
} catch (Throwable $error) {
    $conn->rollback();
    $conn->close();
    fail_test($error->getMessage());
}
