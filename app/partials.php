<?php

function render_public_navigation($active = '') {
    $items = [
        'home' => ['index.php', 'fas fa-home', 'Home'],
        'about' => ['about.php', 'fas fa-building', 'About'],
        'news' => ['news.php', 'fas fa-newspaper', 'News'],
        'donate' => ['donate.php', 'fas fa-donate', 'Donate'],
    ];

    echo '<nav class="navbar">';
    echo '<a class="logo" href="index.php">';
    echo '<img src="image/logooo2.jpg" alt="Charity Donation Management logo">';
    echo '<span>Charity Donation Management</span></a><ul>';
    foreach ($items as $key => $item) {
        echo '<li' . ($key === $active ? ' class="active"' : '') . '>';
        echo '<a href="' . e($item[0]) . '"><i class="' . e($item[1]) . '" aria-hidden="true"></i>' . e($item[2]) . '</a></li>';
    }
    echo '<li' . ($active === 'login' ? ' class="active"' : '') . '>';
    echo '<a href="adlogin.php" class="btn admin"><i class="fas fa-sign-in-alt" aria-hidden="true"></i>Admin</a></li>';
    echo '</ul></nav>';
}

function render_public_footer() {
    echo '<footer class="footer"><div class="footer-content">';
    echo '<div class="footer-section about"><h3>About Us</h3><p>We are a donation hub dedicated to helping communities in need. Join us in making a difference!</p></div>';
    echo '<div class="footer-section links"><h3>Quick Links</h3><ul>';
    echo '<li><a href="index.php">Home</a></li><li><a href="about.php">About</a></li>';
    echo '<li><a href="news.php">News</a></li><li><a href="donate.php">Donate</a></li></ul></div>';
    echo '<div class="footer-section contact"><h3>Contact Us</h3><p>Email: info@charitydonation.com</p><p>Phone: +95 9679 181 879</p></div></div>';
    echo '<div class="footer-section social"><h3>Follow Us</h3><div class="social-icons">';
    echo '<a href="#" aria-label="Facebook"><i class="fab fa-facebook" aria-hidden="true"></i></a>';
    echo '<a href="#" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>';
    echo '<a href="#" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>';
    echo '</div></div><div class="footer-bottom"><p>&copy; ' . date('Y') . ' Charity Donation Management. All rights reserved.</p></div></footer>';
}

function render_flash_messages($successClass = 'message-success', $errorClass = 'message-error') {
    $success = get_flash('success');
    $error = get_flash('error');

    if ($success !== '') {
        echo '<p class="' . e($successClass) . '">' . e($success) . '</p>';
    }
    if ($error !== '') {
        echo '<p class="' . e($errorClass) . '">' . e($error) . '</p>';
    }
}

function render_admin_sidebar($active) {
    $items = [
        'dashboard' => ['addashboard.php', 'fas fa-tachometer-alt', 'Dashboard'],
        'ledger' => ['donation_ledger.php', 'fas fa-book', 'Donation Ledger'],
        'donors' => ['donor.php', 'fas fa-users', 'Donor Directory'],
        'reports' => ['reports.php', 'fas fa-chart-bar', 'Reports'],
        'audit' => ['audit_log.php', 'fas fa-history', 'Audit Log'],
        'foundations' => ['addfoundation.php', 'fas fa-hand-holding-heart', 'Add Foundation'],
        'news' => ['addnews.php', 'fas fa-newspaper', 'Add News'],
        'invite' => ['admin_invite.php', 'fas fa-key', 'Invite Code'],
    ];

    echo '<aside class="sidebar"><h2>Admin Panel</h2><ul>';
    foreach ($items as $key => $item) {
        echo '<li' . ($key === $active ? ' class="active"' : '') . '><a href="' . e($item[0]) . '"><i class="' . e($item[1]) . '"></i>' . e($item[2]) . '</a></li>';
    }
    echo '<li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>';
    echo '</ul></aside>';
}
