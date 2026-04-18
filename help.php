<?php
// Navigation bar include
$unread = isLoggedIn() ? getUnreadNotifications($_SESSION['user_id']) : 0;
$userInitial = isLoggedIn() ? strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) : '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="navbar-brand">
        <span class="icon">🚌</span>
        <span>EC Matatu</span>
    </div>

    <?php if (isLoggedIn()): ?>
    <ul class="navbar-nav" id="navLinks">
        <?php if ($_SESSION['user_role'] === 'passenger'): ?>
            <li><a href="<?= BASE_URL ?>/pages/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">🏠 Home</a></li>
            <li><a href="<?= BASE_URL ?>/pages/trips.php" class="<?= $currentPage === 'trips.php' ? 'active' : '' ?>">🗺️ Trips</a></li>
            <li><a href="<?= BASE_URL ?>/pages/my_bookings.php" class="<?= $currentPage === 'my_bookings.php' ? 'active' : '' ?>">📋 Bookings</a></li>
        <?php elseif ($_SESSION['user_role'] === 'driver'): ?>
            <li><a href="<?= BASE_URL ?>/driver/dashboard.php">🏠 Home</a></li>
            <li><a href="<?= BASE_URL ?>/driver/my_trips.php">🚌 My Trips</a></li>
        <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
            <li><a href="<?= BASE_URL ?>/admin/dashboard.php">🏠 Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/admin/users.php">👥 Users</a></li>
            <li><a href="<?= BASE_URL ?>/admin/routes.php">🗺️ Routes</a></li>
            <li><a href="<?= BASE_URL ?>/admin/trips.php">🚌 Trips</a></li>
            <li><a href="<?= BASE_URL ?>/admin/reports.php">📊 Reports</a></li>
        <?php endif; ?>
    </ul>

    <div class="navbar-user">
        <a href="<?= BASE_URL ?>/pages/notifications.php" style="color:white;position:relative;">
            🔔 <?php if ($unread > 0): ?><span class="badge-notif"><?= $unread ?></span><?php endif; ?>
        </a>
        <div class="avatar" title="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" onclick="window.location='<?= BASE_URL ?>/pages/profile.php'"><?= $userInitial ?></div>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:white;">Logout</a>
    </div>

    <div class="hamburger" id="hamburger">
        <span></span><span></span><span></span>
    </div>
    <?php else: ?>
    <div style="display:flex;gap:10px;">
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-outline" style="border-color:white;color:white;">Login</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-sm btn-secondary">Register</a>
    </div>
    <?php endif; ?>
</nav>
