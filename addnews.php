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
    <title>Add News</title>
    <link rel="stylesheet" href="css/addn.css"> <!-- Link to your existing CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php render_admin_sidebar('news'); ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php if ($message = get_flash('success')): ?><p class="message-success"><?php echo e($message); ?></p><?php endif; ?>
            <?php if ($message = get_flash('error')): ?><p class="message-error"><?php echo e($message); ?></p><?php endif; ?>
            <!-- Add News Form -->
            <div class="form-container">
                <h2>Add News</h2>
                <form action="insert_news.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <!-- Title -->
                    <label for="title">Title:</label>
                    <input type="text" name="title" id="title" required>

                    <!-- Content -->
                    <label for="content">Content:</label>
                    <textarea name="content" id="content" rows="6" required></textarea>

                    <!-- Image Upload -->
                    <label for="image">Upload Image:</label>
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.gif,.webp" required>

                    <!-- Submit Button -->
                    <input type="submit" value="Add News">
                </form>
            </div>

            <!-- Manage News Section -->
            <div class="delete-section">
                <h3>Manage Existing News</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Content</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch news from the database
                        $conn = getDBConnection();

                        $sql = "SELECT nid, image_path, title, content FROM news ORDER BY create_at DESC, nid DESC";
                        $result = $conn->query($sql);
                        $counter=1;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $counter . '</td>';
                                echo '<td><img src="' . e($row["image_path"]) . '" alt="" width="50" height="50"></td>';
                                echo '<td>' . htmlspecialchars($row["title"]) . '</td>';
                                echo '<td>' . htmlspecialchars(substr($row["content"], 0, 50)) . '...</td>';
                                echo '<td><div class="table-actions">
                                <a class="edit-button" href="edit_news.php?id=' . (int) $row["nid"] . '">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                                <a class="preview-button" href="preview_news.php?id=' . (int) $row["nid"] . '">
                                    <i class="fas fa-eye"></i> Preview
                                </a>
                                <form action="delete_news.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                                    <input type="hidden" name="id" value="' . (int) $row["nid"] . '">
                                    <button type="submit" class="delete-button" onclick="return confirm(\'Delete this news item?\')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                                </div></td>';
                                echo '</tr>';
                                $counter++;
                            }
                        } else {
                            echo '<tr><td colspan="5">No news found.</td></tr>';
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
