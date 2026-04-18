<?php
/**
 * Admin sidebar include.
 * Usage: include '../includes/admin_sidebar.php';
 * Requires $currentPage to be set before including:
 *   $currentPage = 'dashboard'; // or users, routes, vehicles, trips, bookings, payments, feedback, reports
 */
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$adminMenu = [
    'dashboard' => ['icon'=>'📊','label'=>'Dashboard','file'=>'dashboard.php'],
    'users'     => ['icon'=>'👥','label'=>'Users',    'file'=>'users.php'],
    'routes'    => ['icon'=>'🗺️','label'=>'Routes',   'file'=>'routes.php'],
    'vehicles'  => ['icon'=>'🚌','label'=>'Vehicles', 'file'=>'vehicles.php'],
    'trips'     => ['icon'=>'📅','label'=>'Trips',    'file'=>'trips.php'],
    'bookings'  => ['icon'=>'📋','label'=>'Bookings', 'file'=>'bookings.php'],
    'payments'  => ['icon'=>'💳','label'=>'Payments', 'file'=>'payments.php'],
    'feedback'      => ['icon'=>'⭐','label'=>'Feedback',      'file'=>'feedback.php'],
    'notifications' => ['icon'=>'🔔','label'=>'Notifications', 'file'=>'notifications.php'],
    'reports'       => ['icon'=>'📈','label'=>'Reports',       'file'=>'reports.php'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-section">Admin Panel</div>
    <?php foreach ($adminMenu as $key => $item): ?>
    <a href="<?= $item['file'] ?>" class="sidebar-item <?= $currentPage === $key ? 'active' : '' ?>">
        <span class="icon"><?= $item['icon'] ?></span> <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
    <div class="sidebar-section">System</div>
    <a href="../pages/profile.php" class="sidebar-item"><span class="icon">👤</span> Profile</a>
    <a href="../pages/notifications.php" class="sidebar-item"><span class="icon">🔔</span> Notifications</a>
    <a href="../logout.php" class="sidebar-item"><span class="icon">🚪</span> Logout</a>
</aside>
