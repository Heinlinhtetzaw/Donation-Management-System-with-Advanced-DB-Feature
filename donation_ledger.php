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
    $where[] = "(d.reference_code LIKE '%{$term}%' OR donor.full_name LIKE '%{$term}%' OR donor.phone LIKE '%{$term}%')";
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $where[] = "d.created_at >= '" . $conn->real_escape_string($from) . " 00:00:00'";
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $where[] = "d.created_at < DATE_ADD('" . $conn->real_escape_string($to) . "', INTERVAL 1 DAY)";
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$total = (int) $conn->query('SELECT COUNT(*) AS total FROM donations d JOIN donors donor ON donor.donor_id = d.donor_id' . $whereSql)->fetch_assoc()['total'];
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$sql = 'SELECT d.id, d.reference_code, donor.full_name, donor.phone, d.amount, d.payment_method, d.payment_status, d.created_at, f.fname AS foundation_name '
    . 'FROM donations d JOIN donors donor ON donor.donor_id = d.donor_id JOIN foundations f ON f.fid = d.foundation_id'
    . $whereSql . ' ORDER BY d.created_at DESC, d.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
$records = $conn->query($sql);
$foundations = $conn->query('SELECT fid, fname FROM foundations ORDER BY fname');
$ledgerSuccess = get_flash('success');
$ledgerError = get_flash('error');
?>
<?php if ($ledgerSuccess !== ''): ?><p><?= e($ledgerSuccess) ?></p><?php endif; ?>
<?php if ($ledgerError !== ''): ?><p><?= e($ledgerError) ?></p><?php endif; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Donation Ledger</title><link rel="stylesheet" href="css/addashb.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"><style>.main-content{margin-left:250px;padding:24px}.filters{display:flex;gap:10px;flex-wrap:wrap;margin:16px 0}.filters input,.filters select,.filters button{padding:9px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;background:#fff}th,td{padding:12px;border:1px solid #ddd;text-align:left}.badge{padding:4px 8px;border-radius:12px;background:#eee;font-size:.85em}.pager a{padding:8px;display:inline-block}.record-link{color:#1565c0;font-weight:bold}.muted{color:#666}</style></head><body><div class="dashboard-container"><?php render_admin_sidebar('ledger'); ?><main class="main-content"><h1>Donation Ledger</h1><p class="muted">Trace, filter, and review every donation without deleting financial records.</p><form class="filters" method="get"><input name="q" value="<?= e($search) ?>" placeholder="Reference, donor, or phone"><select name="status"><option value="">All statuses</option><?php foreach (DONATION_STATUSES as $item): ?><option value="<?= e($item) ?>"<?= $status === $item ? ' selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select><select name="foundation_id"><option value="">All foundations</option><?php while ($foundation = $foundations->fetch_assoc()): ?><option value="<?= (int) $foundation['fid'] ?>"<?= $foundationId === (int) $foundation['fid'] ? ' selected' : '' ?>><?= e($foundation['fname']) ?></option><?php endwhile; ?></select><input type="date" name="from" value="<?= e($from) ?>"><input type="date" name="to" value="<?= e($to) ?>"><button type="submit">Filter</button></form><p><?= $total ?> matching donation<?= $total === 1 ? '' : 's' ?></p><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Donor</th><th>Foundation</th><th>Amount</th><th>Method</th><th>Status</th><th>Received</th></tr></thead><tbody><?php if ($records->num_rows === 0): ?><tr><td colspan="7">No donations match these filters.</td></tr><?php else: while ($row = $records->fetch_assoc()): ?><tr><td><a class="record-link" href="donation_detail.php?id=<?= (int) $row['id'] ?>"><?= e($row['reference_code']) ?></a></td><td><?= e($row['full_name']) ?><br><small><?= e($row['phone']) ?></small></td><td><?= e($row['foundation_name']) ?></td><td><?= number_format((float) $row['amount'], 2) ?> MMK</td><td><?= e($row['payment_method']) ?></td><td><span class="badge"><?= e($row['payment_status']) ?></span></td><td><?= e($row['created_at']) ?></td></tr><?php endwhile; endif; ?></tbody></table></div><nav class="pager"><?php for ($n = 1; $n <= $pages; $n++): $query = $_GET; $query['page'] = $n; ?><a href="?<?= e(http_build_query($query)) ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= $n ?></a><?php endfor; ?></nav></main></div></body></html><?php $conn->close(); ?>
