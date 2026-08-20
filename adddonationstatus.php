<?php
require_once 'auth_check.php';
require_once 'csrf.php';
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/addashb.css"> <!-- Link to your existing CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .main-content {
            margin-left: 250px; /* Match sidebar width */
            padding: 20px;
            width: calc(100% - 250px);
        }
        /* Table Container with Box Shadow */
.table-container {
    margin-top: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
}
table th, table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
}
table th {
    background-color: #2C3E50;
    color: orange;
    font-weight: bold;
}
table td {
    background-color: #f9f9f9;
}

/* Hover Effect for Rows */
table tr:hover {
    background-color: #f1f1f1;
}

/* Link Styling */
table td a {
    color: #3498db;
    text-decoration: none;
}
table td a:hover {
    text-decoration: underline;
}
        /* Color coding for Payment Status */
.status-pending {
    color: #ffd700;
    font-weight: bold;
}
.status-complete {
    color: green;
    font-weight: bold;
}

/* Color coding for Action buttons */
.action-button {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    color: white;
    font-size: 14px;
}
.action-button.pending {
    background-color: orange;
    text-decoration: none;
}
.action-button.pending:hover{
    background-color: #1e1d1d;
    color: orange;
}
.action-button.complete {
    background-color: green;
    text-decoration: none;
}
.action-button.complete:hover{
    background-color: wheat;
    color: green;
}
.action-button:hover {
    opacity: 0.8;
}

    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="addashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li class="active"><a href="adddonationstatus.php"><i class="fas fa-hand-holding-usd"></i>Donations Status</a></li>
                <li><a href="donor.php"><i class="fas fa-users"></i>Donors</a></li>
                <li><a href="addfoundation.php"><i class="fas fa-hand-holding-heart"></i>Add Foundation</a></li>
                <li><a href="addnews.php"><i class="fas fa-newspaper"></i>Add News</a></li>
                <li><a href="admin_invite.php"><i class="fas fa-key"></i>Invite Code</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
<section class="donations-table">
            <h2>Donations Status</h2>
    <div class="table-container">
            
            <table>
    <thead>
        <tr>
            <th>No.</th>
            <th>Donor Name</th>
            <th>Amount</th>
            <th>Foundation</th>
            <th>Payment Method</th>
            <th>Payment Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Fetch donations from the database
        $conn = getDBConnection();

        // Join donations with foundations to get foundation names
        $sql = "SELECT d.id, d.donor_name, d.amount, f.fname AS foundation_name, d.payment_method, d.payment_status 
                FROM donations d
                JOIN foundations f ON d.foundation_id = f.fid";
        $result = $conn->query($sql);
        $counter=1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $counter . '</td>';
                echo '<td>' . htmlspecialchars($row["donor_name"]) . '</td>';
                echo '<td>' . htmlspecialchars($row["amount"]) . '</td>';
                echo '<td>' . htmlspecialchars($row["foundation_name"]) . '</td>';
                echo '<td>' . htmlspecialchars($row["payment_method"]) . '</td>';
                
                if ($row["payment_status"] == "Pending") {
                    echo '<td class="status-pending">' . htmlspecialchars($row["payment_status"]) . '</td>';
                } else {
                    echo '<td class="status-complete">' . htmlspecialchars($row["payment_status"]) . '</td>';
                }
                echo '<td>';
                if ($row["payment_status"] == "Pending") {
                    echo '<form action="update_payment_status.php" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                        <input type="hidden" name="id" value="' . (int) $row["id"] . '">
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" class="action-button complete">Complete</button>
                    </form>';
                } else{
                    echo '<form action="update_payment_status.php" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                        <input type="hidden" name="id" value="' . (int) $row["id"] . '">
                        <input type="hidden" name="action" value="pending">
                        <button type="submit" class="action-button pending">Pending</button>
                    </form>';
                }
                echo '</td>';
                echo '</tr>';
                $counter++;
            }
            
        } else {
            echo '<tr><td colspan="7">No donations found.</td></tr>';
        }
        $conn->close();
        
        ?>
       
        </tbody>
        </table>
    </div>
</section>
        </div>
    </div>
    <!-- Footer -->
    <footer>
        <p>@2025 Donation Hub. All Rights Reserved.</p>
    </footer>
</body>
</html>
