<?php
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$menu = [
    'dashboard'  => ['icon'=>'🏠','label'=>'Dashboard',  'file'=>'dashboard.php'],
    'my_trips'   => ['icon'=>'🚌','label'=>'My Trips',   'file'=>'my_trips.php'],
    'passengers' => ['icon'=>'👥','label'=>'Passengers', 'file'=>'passengers.php'],
    'earnings'   => ['icon'=>'💰','label'=>'Earnings',   'file'=>'earnings.php'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-section">Driver Menu</div>
    <?php foreach ($menu as $key => $item): ?>
    <a href="<?= $item['file'] ?>" class="sidebar-item <?= $currentPage === $key ? 'active' : '' ?>">
        <span class="icon"><?= $item['icon'] ?></span> <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
    <div class="sidebar-section">Account</div>
    <a href="../pages/profile.php" class="sidebar-item"><span class="icon">👤</span> Profile</a>
    <a href="../pages/notifications.php" class="sidebar-item"><span class="icon">🔔</span> Notifications</a>
    <a href="../logout.php" class="sidebar-item"><span class="icon">🚪</span> Logout</a>
</aside>
