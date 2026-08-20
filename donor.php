<?php
require_once 'auth_check.php';
require_once 'csrf.php';
$csrfToken = generate_csrf_token();

// Database connection
$conn = getDBConnection();

// Fetch completed donations
$sql_completed = "SELECT id,donor_name, address, phone, payment_method, payment_status, SUM(amount) AS total_donations, COUNT(*) AS donation_count 
                  FROM donations 
                  WHERE payment_status = 'Complete' 
                  GROUP BY id,donor_name, address, phone, payment_method, payment_status 
                  ORDER BY total_donations DESC";
$result_completed = $conn->query($sql_completed);

// Fetch pending donations
$sql_pending = "SELECT id,donor_name, address, phone, payment_method, payment_status, SUM(amount) AS total_donations, COUNT(*) AS donation_count 
                FROM donations 
                WHERE payment_status = 'Pending' 
                GROUP BY id,donor_name, address, phone, payment_method, payment_status 
                ORDER BY total_donations DESC";
$result_pending = $conn->query($sql_pending);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donors</title>
    <link rel="stylesheet" href="css/donor.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="addashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="adddonationstatus.php"><i class="fas fa-hand-holding-usd"></i>Donation Status</a></li>
                <li class="active"><a href="donor.php"><i class="fas fa-users"></i>Donors</a></li>
                <li><a href="addfoundation.php"><i class="fas fa-hand-holding-heart"></i>Add Foundation</a></li>
                <li><a href="addnews.php"><i class="fas fa-newspaper"></i>Add News</a></li>
                <li><a href="admin_invite.php"><i class="fas fa-key"></i>Invite Code</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="content">
            <h2>Donors Information</h2>

            <!-- Completed Donations Table -->
            <div class="donors-table">
                <h3>Completed Donations</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Donor Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Payment Method</th>
                                <th>Total Donations</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_completed->num_rows > 0) {
                                while ($row = $result_completed->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row["donor_name"]) . '</td>';
                                    echo '<td>' . htmlspecialchars($row["address"]) . '</td>';
                                    echo '<td>' . htmlspecialchars($row["phone"]) . '</td>';
                                    echo '<td>' . htmlspecialchars($row["payment_method"]) . '</td>';
                                    echo '<td>' . number_format($row["total_donations"], 2) . 'MMK</td>';
                                    echo '<td>
                                    <form action="delete_donor.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                                        <input type="hidden" name="id" value="' . (int) $row["id"] . '">
                                        <button type="submit" class="delete-button" onclick="return confirm(\'Delete this donor?\')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                  </td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6">No completed donations found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Donations Table -->
            <div class="donors-table">
                <h3>Pending Donations</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Donor Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Payment Method</th>
                                <th>Total Donations</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_pending->num_rows > 0) {
                                while ($row = $result_pending->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row["donor_name"]) . '</td>';
                                    echo '<td>' . htmlspecialchars($row["address"]) . '</td>';
                                    echo '<td>' . htmlspecialchars($row["phone"]) . '</td>';
                                    echo '<td>' . htmlspecialchars($row["payment_method"]) . '</td>';
                                    echo '<td>' . number_format($row["total_donations"], 2) . 'MMK</td>';
                                    echo '<td>
                                    <form action="delete_donor.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                                        <input type="hidden" name="id" value="' . (int) $row["id"] . '">
                                        <button type="submit" class="delete-button" onclick="return confirm(\'Delete this donor?\')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                  </td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6">No pending donations found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
