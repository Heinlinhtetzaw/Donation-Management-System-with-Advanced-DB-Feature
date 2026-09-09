<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';
require_once __DIR__ . '/app/AuditViewService.php';

$entityLabels = audit_entity_labels();

$entityType = strtolower(trim((string) ($_GET['type'] ?? '')));
$entityId = positive_int($_GET['id'] ?? null);

if (!isset($entityLabels[$entityType]) || $entityId === null) {
    set_flash('error', 'Invalid deleted record.');
    redirect_to('audit_log.php');
}

$conn = getDBConnection();
$entityIdString = (string) $entityId;
$actionType = 'deleted';
$stmt = $conn->prepare(
    'SELECT COALESCE(a.adname, l.admin_username) AS admin_username, '
    . 'l.entity_type, l.entity_id, l.details, l.created_at '
    . 'FROM admin_audit_logs l '
    . 'LEFT JOIN admin a ON a.admin_id = l.admin_id '
    . 'WHERE l.entity_type = ? AND l.entity_id = ? AND l.action_type = ? '
    . 'ORDER BY l.created_at DESC, l.audit_id DESC LIMIT 1'
);
$stmt->bind_param('sss', $entityType, $entityIdString, $actionType);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$record) {
    set_flash('error', 'Deleted record history not found.');
    redirect_to('audit_log.php');
}

$recordLabel = $entityLabels[$entityType] . ' #' . $entityId;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Deleted record: <?= e($recordLabel) ?></title>
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
                <h1><?= e($recordLabel) ?></h1>
                <p>This record was deleted. Its audit history is retained below.</p>
            </div>
            <a class="button" href="audit_log.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to audit log</a>
        </header>

        <section class="panel">
            <div class="detail-grid">
                <div class="detail-card">
                    <small>Record</small>
                    <strong><?= e($recordLabel) ?></strong>
                </div>
                <div class="detail-card">
                    <small>Deleted by</small>
                    <strong><?= e($record['admin_username']) ?></strong>
                </div>
                <div class="detail-card">
                    <small>Deleted at</small>
                    <strong><?= e($record['created_at']) ?></strong>
                </div>
                <div class="detail-card full-width">
                    <small>Details</small>
                    <strong><?= e($record['details']) ?></strong>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
