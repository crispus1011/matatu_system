<?php
/**
 * Passenger sidebar include.
 * Usage: include '../includes/passenger_sidebar.php';
 * $currentPage is auto-detected from basename of current file.
 */
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$menu = [
    'dashboard'       => ['icon'=>'🏠','label'=>'Dashboard',     'file'=>'dashboard.php'],
    'trips'           => ['icon'=>'🗺️','label'=>'Browse Trips',  'file'=>'trips.php'],
    'my_bookings'     => ['icon'=>'📋','label'=>'My Bookings',   'file'=>'my_bookings.php'],
    'payments'        => ['icon'=>'💳','label'=>'Payments',      'file'=>'payments.php'],
];
$accountMenu = [
    'profile'         => ['icon'=>'👤','label'=>'My Profile',    'file'=>'profile.php'],
    'notifications'   => ['icon'=>'🔔','label'=>'Notifications', 'file'=>'notifications.php'],
    'help'            => ['icon'=>'❓','label'=>'Help & FAQ',     'file'=>'help.php'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-section">Menu</div>
    <?php foreach ($menu as $key => $item): ?>
    <a href="<?= $item['file'] ?>" class="sidebar-item <?= $currentPage === $key ? 'active' : '' ?>">
        <span class="icon"><?= $item['icon'] ?></span> <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
    <div class="sidebar-section">Account</div>
    <?php foreach ($accountMenu as $key => $item): ?>
    <a href="<?= $item['file'] ?>" class="sidebar-item <?= $currentPage === $key ? 'active' : '' ?>">
        <span class="icon"><?= $item['icon'] ?></span> <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
    <a href="../logout.php" class="sidebar-item"><span class="icon">🚪</span> Logout</a>
</aside>
