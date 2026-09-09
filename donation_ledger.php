<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';
require_once __DIR__ . '/app/DonationAdminService.php';

$conn = getDBConnection();
$filters = donation_ledger_filters($_GET);
$page = max(1, positive_int($_GET['page'] ?? 1) ?? 1);
$perPage = 20;

$filterValues = [];
$filterTypes = '';
$whereSql = donation_filter_clause($filters, $filterValues, $filterTypes);
$joins = ' FROM donations d LEFT JOIN foundations f ON f.fid = d.foundation_id';

$countStatement = execute_prepared(
    $conn,
    'SELECT COUNT(*) AS total' . $joins . $whereSql,
    $filterTypes,
    $filterValues
);
$total = (int) $countStatement->get_result()->fetch_assoc()['total'];
$countStatement->close();

$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$recordValues = $filterValues;
$recordValues[] = $perPage;
$recordValues[] = $offset;
$recordStatement = execute_prepared(
    $conn,
    'SELECT d.id, d.reference_code, d.donor_name AS full_name, d.phone, d.amount, '
    . 'd.payment_method, d.payment_status, d.created_at, '
    . "COALESCE(f.fname, CONCAT('Missing foundation #', d.foundation_id)) AS foundation_name"
    . $joins . $whereSql
    . ' ORDER BY d.created_at DESC, d.id DESC LIMIT ? OFFSET ?',
    $filterTypes . 'ii',
    $recordValues
);
$records = $recordStatement->get_result();
$foundations = $conn->query('SELECT fid, fname FROM foundations ORDER BY fname');

$paginationQuery = array_filter([
    'q' => $filters['search'],
    'status' => $filters['status'],
    'foundation_id' => $filters['foundation_id'],
    'from' => $filters['from'],
    'to' => $filters['to'],
], static function ($value) {
    return $value !== '' && $value !== null;
});
$pageStart = max(1, $page - 2);
$pageEnd = min($pages, $page + 2);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Donation Ledger</title>
    <link rel="stylesheet" href="css/addashb.css">
    <link rel="stylesheet" href="css/admin-workspace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php render_admin_sidebar('ledger'); ?>
    <main class="workspace-content">
        <header class="workspace-header">
            <div>
                <h1>Donation Ledger</h1>
                <p>Search, review, and safely manage all charity donations.</p>
            </div>
            <a class="button" href="reports.php"><i class="fas fa-chart-bar" aria-hidden="true"></i> View reports</a>
        </header>

        <?php render_flash_messages('flash-success', 'flash-error'); ?>

        <section class="panel">
            <h2><i class="fas fa-filter" aria-hidden="true"></i> Find donations</h2>
            <form class="filters" method="get">
                <label>
                    Search
                    <input name="q" value="<?= e($filters['search']) ?>" placeholder="Reference, donor, phone" maxlength="120">
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="">All statuses</option>
                        <?php foreach (DONATION_STATUSES as $item): ?>
                            <option value="<?= e($item) ?>"<?= $filters['status'] === $item ? ' selected' : '' ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Foundation
                    <select name="foundation_id">
                        <option value="">All foundations</option>
                        <?php while ($foundation = $foundations->fetch_assoc()): ?>
                            <option value="<?= (int) $foundation['fid'] ?>"<?= $filters['foundation_id'] === (int) $foundation['fid'] ? ' selected' : '' ?>><?= e($foundation['fname']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </label>
                <label>From <input type="date" name="from" value="<?= e($filters['from']) ?>"></label>
                <label>To <input type="date" name="to" value="<?= e($filters['to']) ?>"></label>
                <button type="submit"><i class="fas fa-search" aria-hidden="true"></i> Apply filters</button>
            </form>
        </section>

        <section class="panel">
            <h2>Donation records <small>(<?= $total ?> found)</small></h2>
            <div class="table-wrap">
                <table class="workspace-table">
                    <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Donor</th>
                        <th>Foundation</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($records->num_rows === 0): ?>
                        <tr><td colspan="8">No donations match your filters.</td></tr>
                    <?php else: ?>
                        <?php while ($row = $records->fetch_assoc()): ?>
                            <?php $statusClass = css_class_token($row['payment_status']); ?>
                            <tr>
                                <td><a class="record-link" href="donation_detail.php?id=<?= (int) $row['id'] ?>"><?= e($row['reference_code']) ?></a></td>
                                <td><?= e($row['full_name']) ?><br><small><?= e($row['phone']) ?></small></td>
                                <td><?= e($row['foundation_name']) ?></td>
                                <td><?= e(format_mmk($row['amount'])) ?></td>
                                <td><?= e($row['payment_method']) ?></td>
                                <td><span class="status-badge <?= e($statusClass) ?>"><?= e($row['payment_status']) ?></span></td>
                                <td><?= e($row['created_at']) ?></td>
                                <td><a class="button" href="donation_detail.php?id=<?= (int) $row['id'] ?>">Review</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="pagination" aria-label="Donation ledger pages">
                    <?php if ($page > 1): ?>
                        <?php $previousQuery = array_merge($paginationQuery, ['page' => $page - 1]); ?>
                        <a href="?<?= e(http_build_query($previousQuery)) ?>" aria-label="Previous page">&lsaquo;</a>
                    <?php endif; ?>
                    <?php for ($number = $pageStart; $number <= $pageEnd; $number++): ?>
                        <?php $pageQuery = array_merge($paginationQuery, ['page' => $number]); ?>
                        <a href="?<?= e(http_build_query($pageQuery)) ?>"<?= $number === $page ? ' aria-current="page"' : '' ?>><?= $number ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $pages): ?>
                        <?php $nextQuery = array_merge($paginationQuery, ['page' => $page + 1]); ?>
                        <a href="?<?= e(http_build_query($nextQuery)) ?>" aria-label="Next page">&rsaquo;</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
<?php
$recordStatement->close();
$conn->close();
