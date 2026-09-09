<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';
require_once __DIR__ . '/app/AuditViewService.php';

$conn = getDBConnection();
$logs = $conn->query(
    'SELECT COALESCE(a.adname, l.admin_username) AS admin_username, '
    . 'l.action_type, l.entity_type, l.entity_id, l.details, l.created_at '
    . 'FROM admin_audit_logs l '
    . 'LEFT JOIN admin a ON a.admin_id = l.admin_id '
    . 'ORDER BY l.created_at DESC, l.audit_id DESC LIMIT 200'
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Audit Log</title>
    <link rel="stylesheet" href="css/addashb.css">
    <link rel="stylesheet" href="css/admin-workspace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php render_admin_sidebar('audit'); ?>
    <main class="workspace-content">
        <header class="workspace-header">
            <div>
                <h1>Audit Log</h1>
                <p>Latest 200 administrator actions across donations, foundations, and news.</p>
            </div>
            <a class="button" href="donation_ledger.php">Open ledger</a>
        </header>
        <?php render_flash_messages('flash-success', 'flash-error'); ?>
        <section class="panel">
            <div class="table-wrap">
                <table class="workspace-table">
                    <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Record</th><th>Details</th></tr></thead>
                    <tbody>
                    <?php if ($logs->num_rows === 0): ?>
                        <tr><td colspan="5">No audited activity has been recorded yet.</td></tr>
                    <?php else: while ($row = $logs->fetch_assoc()): ?>
                        <?php
                        $recordLabel = audit_record_label($row['entity_type'], $row['entity_id']);
                        $recordUrl = audit_record_url($row['entity_type'], $row['entity_id'], $row['action_type']);
                        ?>
                        <tr>
                            <td><?= e($row['created_at']) ?></td>
                            <td><?= e($row['admin_username']) ?></td>
                            <td><?= e(ucwords(str_replace('_', ' ', $row['action_type']))) ?></td>
                            <td>
                                <?php if ($recordUrl !== null): ?>
                                    <a class="audit-record-link" href="<?= e($recordUrl) ?>" aria-label="Open <?= e($recordLabel) ?>">
                                        <i class="fas fa-external-link-alt" aria-hidden="true"></i> <?= e($recordLabel) ?>
                                    </a>
                                <?php else: ?>
                                    <?= e($recordLabel) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['details']) ?></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
<?php $conn->close(); ?>
