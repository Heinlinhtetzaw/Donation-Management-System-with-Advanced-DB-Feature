<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';
require_once __DIR__ . '/app/DonationAdminService.php';

$conn = getDBConnection();
$status = $_GET['status'] ?? '';
$foundationId = positive_int($_GET['foundation_id'] ?? null);
$search = trim($_GET['q'] ?? '');
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$page = max(1, positive_int($_GET['page'] ?? 1) ?? 1);
$perPage = 20;
$where = [];
if (in_array($status, DONATION_STATUSES, true)) $where[] = "d.payment_status = '" . $conn->real_escape_string($status) . "'";
if ($foundationId !== null) $where[] = 'd.foundation_id = ' . $foundationId;
if ($search !== '') {
    $term = $conn->real_escape_string($search);
    $pattern = "'%{$term}%' COLLATE utf8mb4_unicode_ci";
    $where[] = "(d.reference_code COLLATE utf8mb4_unicode_ci LIKE {$pattern}"
        . " OR d.donor_name COLLATE utf8mb4_unicode_ci LIKE {$pattern}"
        . " OR d.phone COLLATE utf8mb4_unicode_ci LIKE {$pattern})";
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $where[] = "d.created_at >= '" . $conn->real_escape_string($from) . " 00:00:00'";
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $where[] = "d.created_at < DATE_ADD('" . $conn->real_escape_string($to) . "', INTERVAL 1 DAY)";
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$joins = ' FROM donations d LEFT JOIN foundations f ON f.fid=d.foundation_id';
$total = (int) $conn->query('SELECT COUNT(*) total' . $joins . $whereSql)->fetch_assoc()['total'];
$pages = max(1, (int) ceil($total / $perPage)); $page = min($page, $pages); $offset = ($page - 1) * $perPage;
$records = $conn->query('SELECT d.id,d.reference_code,d.donor_name full_name,d.phone,d.amount,d.payment_method,d.payment_status,d.created_at,COALESCE(f.fname,CONCAT(\'Missing foundation #\',d.foundation_id)) foundation_name' . $joins . $whereSql . ' ORDER BY d.created_at DESC,d.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
$foundations = $conn->query('SELECT fid,fname FROM foundations ORDER BY fname');
$success = get_flash('success'); $error = get_flash('error');
function status_class($value) { return strtolower(str_replace(' ', '-', $value)); }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Donation Ledger</title><link rel="stylesheet" href="css/addashb.css"><link rel="stylesheet" href="css/admin-workspace.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"></head>
<body><div class="dashboard-container"><?php render_admin_sidebar('ledger'); ?>
<main class="workspace-content"><header class="workspace-header"><div><h1>Donation Ledger</h1><p>Search, review, and safely manage all charity donations.</p></div><a class="button" href="reports.php"><i class="fas fa-chart-bar"></i> View reports</a></header>
<?php if ($success !== ''): ?><p class="flash-success"><?= e($success) ?></p><?php endif; ?><?php if ($error !== ''): ?><p class="flash-error"><?= e($error) ?></p><?php endif; ?>
<section class="panel"><h2><i class="fas fa-filter"></i> Find donations</h2><form class="filters" method="get"><label>Search<input name="q" value="<?= e($search) ?>" placeholder="Reference, donor, phone"></label><label>Status<select name="status"><option value="">All statuses</option><?php foreach (DONATION_STATUSES as $item): ?><option value="<?= e($item) ?>"<?= $status === $item ? ' selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label><label>Foundation<select name="foundation_id"><option value="">All foundations</option><?php while ($foundation = $foundations->fetch_assoc()): ?><option value="<?= (int)$foundation['fid'] ?>"<?= $foundationId === (int)$foundation['fid'] ? ' selected' : '' ?>><?= e($foundation['fname']) ?></option><?php endwhile; ?></select></label><label>From<input type="date" name="from" value="<?= e($from) ?>"></label><label>To<input type="date" name="to" value="<?= e($to) ?>"></label><button type="submit"><i class="fas fa-search"></i> Apply filters</button></form></section>
<section class="panel"><h2>Donation records <small>(<?= $total ?> found)</small></h2><div class="table-wrap"><table class="workspace-table"><thead><tr><th>Reference</th><th>Donor</th><th>Foundation</th><th>Amount</th><th>Method</th><th>Status</th><th>Received</th><th>Action</th></tr></thead><tbody><?php if ($records->num_rows === 0): ?><tr><td colspan="8">No donations match your filters.</td></tr><?php else: while ($row = $records->fetch_assoc()): ?><tr><td><a class="record-link" href="donation_detail.php?id=<?= (int)$row['id'] ?>"><?= e($row['reference_code']) ?></a></td><td><?= e($row['full_name']) ?><br><small><?= e($row['phone']) ?></small></td><td><?= e($row['foundation_name']) ?></td><td><?= number_format((float)$row['amount'], 2) ?> MMK</td><td><?= e($row['payment_method']) ?></td><td><span class="status-badge <?= e(status_class($row['payment_status'])) ?>"><?= e($row['payment_status']) ?></span></td><td><?= e($row['created_at']) ?></td><td><a class="button" href="donation_detail.php?id=<?= (int)$row['id'] ?>">Review</a></td></tr><?php endwhile; endif; ?></tbody></table></div><nav class="pagination"><?php for ($n=1; $n <= $pages; $n++): $query=$_GET; $query['page']=$n; ?><a href="?<?= e(http_build_query($query)) ?>"<?= $n===$page ? ' aria-current="page"' : '' ?>><?= $n ?></a><?php endfor; ?></nav></section></main></div></body></html>
<?php $conn->close(); ?>
