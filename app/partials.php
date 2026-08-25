<?php

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
