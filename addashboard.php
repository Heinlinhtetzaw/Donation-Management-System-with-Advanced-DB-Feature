<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';

$conn = getDBConnection();
$stats = $conn->query(
    "SELECT "
    . "COALESCE(SUM(CASE WHEN payment_status = 'Complete' AND verified_at IS NOT NULL THEN amount ELSE 0 END), 0) AS completed_total, "
    . "(SELECT COUNT(*) FROM donors) AS donor_count, "
    . "SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) AS pending_count, "
    . "SUM(CASE WHEN payment_status = 'Complete' THEN 1 ELSE 0 END) AS completed_count "
    . 'FROM donations'
)->fetch_assoc();

$recentDonations = $conn->query(
    'SELECT d.id, d.reference_code, d.donor_name, d.amount, d.payment_method, '
    . 'd.payment_status, d.created_at, '
    . "COALESCE(f.fname, CONCAT('Missing foundation #', d.foundation_id)) AS foundation_name "
    . 'FROM donations d LEFT JOIN foundations f ON f.fid = d.foundation_id '
    . 'ORDER BY d.created_at DESC, d.id DESC LIMIT 10'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/addashb.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php render_admin_sidebar('dashboard'); ?>
    <main class="content">
        <header><h1>Dashboard</h1></header>
        <section class="stats">
            <div class="stat-card">Verified total: <?= e(format_mmk($stats['completed_total'])) ?></div>
            <div class="stat-card">Total donors: <?= (int) $stats['donor_count'] ?></div>
            <div class="stat-card">Pending donations: <?= (int) $stats['pending_count'] ?></div>
            <div class="stat-card">Completed donations: <?= (int) $stats['completed_count'] ?></div>
        </section>

        <section class="donations-table">
            <h2>Recent Donations</h2>
            <table>
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Donor</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Foundation</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($recentDonations->num_rows === 0): ?>
                    <tr><td colspan="7">No recent donations found.</td></tr>
                <?php else: ?>
                    <?php while ($row = $recentDonations->fetch_assoc()): ?>
                        <?php $statusClass = $row['payment_status'] === 'Pending' ? 'status-pending' : 'status-complete'; ?>
                        <tr>
                            <td><a href="donation_detail.php?id=<?= (int) $row['id'] ?>"><?= e($row['reference_code']) ?></a></td>
                            <td><?= e($row['donor_name']) ?></td>
                            <td><?= e(format_mmk($row['amount'])) ?></td>
                            <td><?= e($row['payment_method']) ?></td>
                            <td><?= e($row['foundation_name']) ?></td>
                            <td class="<?= e($statusClass) ?>"><?= e($row['payment_status']) ?></td>
                            <td><?= e($row['created_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>
<?php $conn->close();
