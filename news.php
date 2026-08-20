<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Landing Page</title>
    <link rel="stylesheet" href="css/news.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo"><image src="./image/logooo2.jpg">
            <span>Charity Donation Management</span>
        </div>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i>Home</a></li>
            <li><a href="about.php"><i class="fas fa-building"></i>About</a></li>
            <li class="active"><a href="news.php"><i class="fas fa-newspaper"></i>News</a></li>
            <li><a href="donate.php"><i class="fas fa-donate"></i>Donate</a></li>
            <li><a href="signup.php" class="btn admin"><i class="fas fa-user-plus"></i>Signup</a></li>
        </ul>
    </nav>
    <div class="hero">
    
     <!-- News Section -->
     <div class="news-container">

     <?php
// Database connection
$conn = getDBConnection();

// Fetch news data
$sql = "SELECT nid, title, content, image_path, create_at FROM news ORDER BY create_at DESC";
$result = $conn->query($sql);
?>
 <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="news-card">';
                echo '<div class="image" style="background-image: url(\'' . $row["image_path"] . '\');"></div>';
                echo '<div class="content">';
                echo '<h3>' . htmlspecialchars($row["title"]) . '</h3>';
                echo '<div class="date">' . htmlspecialchars($row["create_at"]) . '</div>';
                echo '<p>' . htmlspecialchars(substr($row["content"], 0, 150)) . '...</p>'; // Short preview
                echo '<div class="read-more-content">';
                echo '<p>' . htmlspecialchars($row["content"]) . '</p>'; // Full content
                echo '<div class="buttons">';
                    echo '<a href="donate.php" class="donate-btn">Donate</a>';
                echo '</div>';
                echo '</div>';
                echo '<a class="read-more-btn">Read More</a>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo "<p>No news available.</p>";
        }
        ?>
    </div>
  
    </div>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section about">
                <h3>About Us</h3>
                <p>We are a donation hub dedicated to helping communities in need. Join us in making a difference!</p>
            </div>
            <div class="footer-section links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="news.php">News</a></li>
                    <li><a href="donate.php">Donate</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fas fa-envelope"></i></a><p>Email: info@charitydonation.com</p>
                    <a href="#"><i class="fas fa-phone"></i></a><p>Phone: +95 9679 181 879</p>
                </div>
            </div>
        </div>
        <div class="footer-section social">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Charity Donation Management. All rights reserved.</p>
        </div>
    </footer>
    <script src="js/news.js"></script>
</body>
</html>
