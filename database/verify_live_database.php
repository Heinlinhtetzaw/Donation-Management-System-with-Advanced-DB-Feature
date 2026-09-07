<?php
// Run from the project root: php database/verify_live_database.php
// This script rolls back every test write before it exits.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/AdminAuditService.php';

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
    'foundations' => ['fid', 'created_by_admin_id', 'image_path', 'fname', 'description', 'intro'],
    'news' => ['nid', 'created_by_admin_id', 'image_path', 'title', 'content', 'create_at'],
    'donors' => ['donor_id', 'full_name', 'address', 'phone', 'created_at'],
    'donations' => ['id', 'donor_id', 'reference_code', 'donor_name', 'address', 'phone', 'amount', 'foundation_id', 'payment_method', 'payment_status', 'verified_at', 'verified_by', 'verified_by_admin_id', 'status_note', 'created_at'],
    'donation_status_history' => ['history_id', 'donation_id', 'previous_status', 'new_status', 'note', 'changed_by', 'changed_by_admin_id', 'changed_at'],
    'admin_audit_logs' => ['audit_id', 'admin_id', 'admin_username', 'action_type', 'entity_type', 'entity_id', 'details', 'created_at'],
];

$requiredForeignKeys = [
    'donations.verified_by_admin_id' => 'admin.admin_id',
    'donation_status_history.changed_by_admin_id' => 'admin.admin_id',
    'admin_audit_logs.admin_id' => 'admin.admin_id',
    'foundations.created_by_admin_id' => 'admin.admin_id',
    'news.created_by_admin_id' => 'admin.admin_id',
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

    $foreignKeyResult = $conn->query(
        "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME "
        . "FROM information_schema.KEY_COLUMN_USAGE "
        . "WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL"
    );
    $actualForeignKeys = [];
    while ($foreignKey = $foreignKeyResult->fetch_assoc()) {
        $actualForeignKeys[$foreignKey['TABLE_NAME'] . '.' . $foreignKey['COLUMN_NAME']] =
            $foreignKey['REFERENCED_TABLE_NAME'] . '.' . $foreignKey['REFERENCED_COLUMN_NAME'];
    }
    foreach ($requiredForeignKeys as $column => $reference) {
        assert_test(($actualForeignKeys[$column] ?? null) === $reference, "Foreign key '{$column}' does not reference '{$reference}'.");
    }
    echo "PASS: administrator foreign keys" . PHP_EOL;

    $admin = $conn->query('SELECT admin_id, adname FROM admin ORDER BY admin_id ASC LIMIT 1')->fetch_assoc();
    assert_test($admin !== null, 'At least one administrator is required for the administration flow test.');
    $adminId = (int) $admin['admin_id'];
    $adminUsername = $admin['adname'];
    $_SESSION['admin_username'] = $adminUsername;
    unset($_SESSION['admin_id']);
    $resolvedAdmin = current_admin_identity($conn);
    assert_test($resolvedAdmin['id'] === $adminId, 'Legacy username-only session did not resolve admin_id.');
    assert_test($resolvedAdmin['username'] === $adminUsername, 'Resolved administrator username did not match.');
    echo "PASS: administrator session compatibility" . PHP_EOL;

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
    $update = $conn->prepare('UPDATE donations SET payment_status = ?, verified_by = ?, verified_by_admin_id = ? WHERE id = ?');
    $update->bind_param('ssii', $complete, $adminUsername, $adminId, $donationId);
    $update->execute();
    assert_test($update->affected_rows === 1, 'Could not update transactional donation status.');
    $update->close();

    $status = $conn->query(
        "SELECT d.payment_status, COALESCE(a.adname, d.verified_by) AS verified_by_display "
        . "FROM donations d LEFT JOIN admin a ON a.admin_id = d.verified_by_admin_id "
        . "WHERE d.id = {$donationId}"
    )->fetch_assoc();
    assert_test($status !== null && $status['payment_status'] === 'Complete', 'Updated donation status was not persisted.');
    assert_test($status['verified_by_display'] === $adminUsername, 'Donation verifier username was not resolved through admin_id.');
    echo "PASS: donation status update" . PHP_EOL;

    $history = $conn->prepare("INSERT INTO donation_status_history (donation_id, previous_status, new_status, note, changed_by, changed_by_admin_id) VALUES (?, 'Pending', 'Complete', 'Flow test', ?, ?)");
    $history->bind_param('isi', $donationId, $adminUsername, $adminId);
    $history->execute();
    $history->close();

    write_admin_audit($conn, $resolvedAdmin, 'status_changed', 'donation', $donationId, 'Flow test');

    $testImage = 'image/test-placeholder.jpg';
    $testFoundationName = '__foundation_admin_link_test__';
    $testDescription = 'Relationship verification';
    $testIntro = 'Rolled back after verification';
    $foundationInsert = $conn->prepare('INSERT INTO foundations (created_by_admin_id, image_path, fname, description, intro) VALUES (?, ?, ?, ?, ?)');
    $foundationInsert->bind_param('issss', $adminId, $testImage, $testFoundationName, $testDescription, $testIntro);
    $foundationInsert->execute();
    $foundationInsert->close();

    $testNewsTitle = '__news_admin_link_test__';
    $testNewsContent = 'Relationship verification; rolled back after testing.';
    $newsInsert = $conn->prepare('INSERT INTO news (created_by_admin_id, image_path, title, content) VALUES (?, ?, ?, ?)');
    $newsInsert->bind_param('isss', $adminId, $testImage, $testNewsTitle, $testNewsContent);
    $newsInsert->execute();
    $newsInsert->close();
    echo "PASS: administrator attribution flow" . PHP_EOL;

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
