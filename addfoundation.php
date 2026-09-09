<?php
require_once 'auth_check.php';
require_once 'csrf.php';
require_once __DIR__ . '/app/partials.php';
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
        <?php render_admin_sidebar('foundations'); ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php if ($message = get_flash('success')): ?><p class="message-success"><?php echo e($message); ?></p><?php endif; ?>
            <?php if ($message = get_flash('error')): ?><p class="message-error"><?php echo e($message); ?></p><?php endif; ?>
            <!-- Add Foundation Form -->
            <div class="form-container">
                <h2>Add Foundation</h2>
                <form action="insert_foundation.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <!-- Image Upload -->
                    <label for="image">Upload Image:</label>
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.gif,.webp" required>

                    <!-- Frame -->
                    <label for="fname">Foundation-name:</label>
                    <textarea name="fname" id="fname" rows="4" maxlength="150" required></textarea>

                    <!-- Description -->
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="4" maxlength="1000" required></textarea>

                    <!-- Intro -->
                    <label for="intro">Introduction:</label>
                    <textarea name="intro" id="intro" rows="4" maxlength="5000" required></textarea>


                    <!-- Submit Button -->
                    <input type="submit" value="Add Foundation">

                </form>
            </div>

            <!-- Manage Foundations Section -->
            <div class="delete-section">
                <h3>Manage Existing Foundations</h3>
                <table>
                    <thead>
                        <tr>
                            <th>NO.</th>
                            <th>Image</th>
                            <th>Foundation-name</th>
                            <th>Description</th>
                            <th>Created by</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch foundations from the database
                        $conn = getDBConnection();

                        $sql = "SELECT f.fid, f.image_path, f.fname, f.description, COALESCE(a.adname, 'Legacy / unknown') AS created_by FROM foundations f LEFT JOIN admin a ON a.admin_id = f.created_by_admin_id ORDER BY f.fid DESC";
                        $result = $conn->query($sql);
                        $counter=1;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $counter. '</td>';
                                echo '<td><img src="' . e(public_asset_path($row["image_path"])) . '" alt="" width="50" height="50"></td>';
                                echo '<td>' . e($row["fname"]) . '</td>';
                                echo '<td>' . e(text_excerpt($row["description"], 100)) . '</td>';
                                echo '<td>' . e($row["created_by"]) . '</td>';
                                echo '<td><div class="table-actions">
                                <a class="edit-button" href="edit_foundation.php?id=' . (int) $row["fid"] . '">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                                <a class="preview-button" href="preview_foundation.php?id=' . (int) $row["fid"] . '">
                                    <i class="fas fa-eye"></i> Preview
                                </a>
                                <form action="delete_foundation.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="' . e($csrfToken) . '">
                                    <input type="hidden" name="id" value="' . (int) $row["fid"] . '">
                                    <button type="submit" class="delete-button" onclick="return confirm(\'Delete this foundation?\')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                                </div></td>';
                                echo '</tr>';
                                $counter++;
                            }
                        } else {
                            echo '<tr><td colspan="6">No foundations found.</td></tr>';
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
        <p>&copy; <?= date('Y') ?> Donation Hub. All Rights Reserved.</p>
    </footer>
</body>
</html>
