<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';

$conn = getDBConnection();
$search = text_limit(request_string($_GET, 'q'), 120);
$statement = null;
if ($search === '') {
    $donors = $conn->query(
        'SELECT donor_id, full_name, phone, address, created_at '
        . 'FROM donors ORDER BY created_at DESC, donor_id DESC LIMIT 200'
    );
} else {
    $pattern = '%' . $search . '%';
    $statement = execute_prepared(
        $conn,
        'SELECT donor_id, full_name, phone, address, created_at '
        . 'FROM donors WHERE full_name LIKE ? OR phone LIKE ? '
        . 'ORDER BY created_at DESC, donor_id DESC LIMIT 200',
        'ss',
        [$pattern, $pattern]
    );
    $donors = $statement->get_result();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Donor Directory</title>
    <link rel="stylesheet" href="css/addashb.css">
    <link rel="stylesheet" href="css/admin-workspace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php render_admin_sidebar('donors'); ?>
    <main class="workspace-content">
        <header class="workspace-header">
            <div>
                <h1>Donor Directory</h1>
                <p>Reusable donor records linked safely to donation history.</p>
            </div>
            <a class="button" href="donation_ledger.php">Donation Ledger</a>
        </header>

        <section class="panel">
            <h2><i class="fas fa-search" aria-hidden="true"></i> Search donors</h2>
            <form class="filters" method="get">
                <label>
                    Donor name or phone
                    <input name="q" value="<?= e($search) ?>" placeholder="Search name or phone" maxlength="120">
                </label>
                <button type="submit">Search directory</button>
            </form>
        </section>

        <section class="panel">
            <h2>Donor records</h2>
            <div class="table-wrap">
                <table class="workspace-table">
                    <thead>
                    <tr><th>Donor</th><th>Phone</th><th>Address</th><th>Joined</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($donors->num_rows === 0): ?>
                        <tr><td colspan="5">No donor records found.</td></tr>
                    <?php else: ?>
                        <?php while ($donor = $donors->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($donor['full_name']) ?></td>
                                <td><?= e($donor['phone']) ?></td>
                                <td><?= e($donor['address']) ?></td>
                                <td><?= e($donor['created_at']) ?></td>
                                <td><a class="button" href="donation_ledger.php?q=<?= e(rawurlencode($donor['phone'])) ?>">View donations</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p><small>Showing at most 200 donor records. Use search to narrow the directory.</small></p>
        </section>
    </main>
</div>
</body>
</html>
<?php
if ($statement !== null) {
    $statement->close();
}
$conn->close();
