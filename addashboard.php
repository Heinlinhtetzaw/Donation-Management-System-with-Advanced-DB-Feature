<?php
require_once 'auth_check.php';
require_once __DIR__ . '/app/partials.php';

// Database connection
$conn = getDBConnection();

// Fetch total donations where payment_status is "Complete"
$sql_total_donations = "SELECT COALESCE(SUM(amount), 0) AS total_donations FROM donations WHERE payment_status = 'Complete' AND verified_at IS NOT NULL";
$result_total_donations = $conn->query($sql_total_donations);
$total_donations = $result_total_donations->fetch_assoc()["total_donations"];

// Fetch total donors
$sql_total_donors = "SELECT COUNT(*) AS total_donors FROM donors";
$result_total_donors = $conn->query($sql_total_donors);
$total_donors = $result_total_donors->fetch_assoc()["total_donors"];

// Fetch pending donations
$sql_pending_donations = "SELECT COUNT(*) AS pending_donations FROM donations WHERE payment_status = 'Pending'";
$result_pending_donations = $conn->query($sql_pending_donations);
$pending_donations = $result_pending_donations->fetch_assoc()["pending_donations"];

// Fetch completed donations
$sql_completed_donations = "SELECT COUNT(*) AS completed_donations FROM donations WHERE payment_status = 'Complete'";
$result_completed_donations = $conn->query($sql_completed_donations);
$completed_donations = $result_completed_donations->fetch_assoc()["completed_donations"];

// Fetch recent donations (last 10 donations)
$sql_recent_donations = "SELECT d.donor_name, d.amount, d.payment_method, f.fname AS foundation_name, d.payment_status, d.created_at 
                         FROM donations d
                         JOIN foundations f ON d.foundation_id = f.fid
                         ORDER BY d.created_at DESC
                         LIMIT 10";
$result_recent_donations = $conn->query($sql_recent_donations);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/addashb.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <?php render_admin_sidebar('dashboard'); ?>
    <main class="content">
        <header>
            <h1>Dashboard</h1>
        </header>
        <section class="stats">
            <div class="stat-card">Total Amount: <?php echo number_format($total_donations, 2); ?> MMK</div>
            <div class="stat-card">Total Donors: <?php echo $total_donors; ?></div>
            <div class="stat-card">Pending Donations: <?php echo $pending_donations; ?></div>
            <div class="stat-card">Completed Donations: <?php echo $completed_donations; ?></div>
        </section>
        <section class="donations-table">
            <h2>Recent Donations</h2>
            <table>
                <tr>
                    <th>Donor</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Foundation</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
                <?php
                if ($result_recent_donations->num_rows > 0) {
                    while ($row = $result_recent_donations->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row["donor_name"]) . '</td>';
                        echo '<td>' . number_format($row["amount"], 2) . 'MMK</td>';
                        echo '<td>' . htmlspecialchars($row["payment_method"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["foundation_name"]) . '</td>';
                        // Add color coding for Payment Status
                if ($row["payment_status"] == "Pending") {
                    echo '<td class="status-pending">' . htmlspecialchars($row["payment_status"]) . '</td>';
                } else {
                    echo '<td class="status-complete">' . htmlspecialchars($row["payment_status"]) . '</td>';
                }
                        echo '<td>' . htmlspecialchars($row["created_at"]) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">No recent donations found.</td></tr>';
                }
                ?>
            </table>
        </section>
    </main>
    </div>
    <!-- Footer -->
    <footer>
        <p>@2025 Donation Hub. All Rights Reserved.</p>
    </footer>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
