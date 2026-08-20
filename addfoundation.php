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
    <title>Add Foundation</title>
    <link rel="stylesheet" href="css/addf.css"> <!-- Link to your existing CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="addashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="adddonationstatus.php"><i class="fas fa-hand-holding-usd"></i>Donations Status</a></li>
                <li><a href="donor.php"><i class="fas fa-users"></i>Donors</a></li>
                <li class="active"><a href="addfoundation.php"><i class="fas fa-hand-holding-heart"></i>Add Foundation</a></li>
                <li><a href="addnews.php"><i class="fas fa-newspaper"></i>Add News</a></li>
                <li><a href="admin_invite.php"><i class="fas fa-key"></i>Invite Code</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Add Foundation Form -->
            <div class="form-container">
                <h2>Add Foundation</h2>
                <form action="insert_foundation.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <!-- Image Upload -->
                    <label for="image">Upload Image:</label>
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.gif,.webp" required>

                    <!-- Frame -->
                    <label for="fname">Foundation-name:</label>
                    <textarea name="fname" id="fname" rows="4" required></textarea>

                    <!-- Description -->
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="4" required></textarea>

                    <!-- Intro -->
                    <label for="intro">Introduction:</label>
                    <textarea name="intro" id="intro" rows="4" required></textarea>


                    <!-- Submit Button -->
                    <input type="submit" value="Add Foundation">

                </form>
            </div>

            <!-- Delete Foundations Section -->
            <div class="delete-section">
                <h3>Delete Existing Foundations</h3>
                <table>
                    <thead>
                        <tr>
                            <th>NO.</th>
                            <th>Image</th>
                            <th>Foundation-name</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch foundations from the database
                        $conn = getDBConnection();

                        $sql = "SELECT fid, image_path, fname, description FROM foundations";
                        $result = $conn->query($sql);
                        $counter=1;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $counter. '</td>';
                                echo '<td><img src="' . $row["image_path"] . '" width="50" height="50"></td>';
                                echo '<td>' . htmlspecialchars($row["fname"]) . '</td>';
                                echo '<td>' . htmlspecialchars($row["description"]) . '</td>';
                                echo '<td>
                                <form action="delete_foundation.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                                    <input type="hidden" name="id" value="' . (int) $row["fid"] . '">
                                    <button type="submit" class="delete-button" onclick="return confirm(\'Delete this foundation?\')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                                </td>';
                                echo '</tr>';
                                $counter++;
                            }
                        } else {
                            echo '<tr><td colspan="5">No foundations found.</td></tr>';
                        }

                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <footer>
        <p>@2025 Donation Hub. All Rights Reserved.</p>
    </footer>
</body>
</html>
