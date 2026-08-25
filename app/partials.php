<?php

function render_admin_sidebar($active) {
    $items = [
        'dashboard' => ['addashboard.php', 'fas fa-tachometer-alt', 'Dashboard'],
        'donations' => ['adddonationstatus.php', 'fas fa-hand-holding-usd', 'Donations Status'],
        'donors' => ['donor.php', 'fas fa-users', 'Donors'],
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
