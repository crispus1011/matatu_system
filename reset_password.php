<?php
require_once '../includes/auth.php';
requireRole('passenger', '../login.php');
$user = getCurrentUser();

// Stats
$userId = $_SESSION['user_id'];
$totalBookings = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE passenger_id=$userId")->fetch_assoc()['c'];
$completedTrips = $conn->query("SELECT COUNT(*) as c FROM bookings b JOIN trips t ON b.trip_id=t.id WHERE b.passenger_id=$userId AND t.status='completed'")->fetch_assoc()['c'];
$totalSpent = $conn->query("SELECT IFNULL(SUM(amount),0) as s FROM payments WHERE passenger_id=$userId AND payment_status='completed'")->fetch_assoc()['s'];
$activeBookings = $conn->query("SELECT COUNT(*) as c FROM bookings b JOIN trips t ON b.trip_id=t.id WHERE b.passenger_id=$userId AND b.booking_status='confirmed' AND t.status IN ('scheduled','ongoing')")->fetch_assoc()['c'];

// Recent bookings
$recentBookings = $conn->query("
    SELECT b.*, r.route_name, r.origin, r.destination, t.departure_time, t.status as trip_status, p.payment_status
    FROM bookings b
    JOIN trips t ON b.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.passenger_id = $userId
    ORDER BY b.created_at DESC LIMIT 5
");

// Upcoming trips
$upcomingTrips = $conn->query("
    SELECT t.*, r.route_name, r.origin, r.destination, r.fare, v.registration_no, u.full_name as driver_name
    FROM trips t
    JOIN routes r ON t.route_id = r.id
    JOIN vehicles v ON t.vehicle_id = v.id
    JOIN users u ON t.driver_id = u.id
    WHERE t.status = 'scheduled' AND t.departure_time > NOW() AND t.available_seats > 0
    ORDER BY t.departure_time ASC LIMIT 6
");

// Notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id=$userId ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="layout">
    <!-- Sidebar -->
    <?php $currentPage = "dashboard"; include '../includes/passenger_sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Welcome back, <?= htmlspecialchars($user['full_name']) ?>! 👋</h1>
                <p class="page-subtitle">Here's what's happening with your account today.</p>
            </div>
            <a href="trips.php" class="btn btn-primary">🚌 Book a Trip</a>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">📋</div>
                <div>
                    <div class="stat-value counter" data-target="<?= $totalBookings ?>"><?= $totalBookings ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">✅</div>
                <div>
                    <div class="stat-value counter" data-target="<?= $completedTrips ?>"><?= $completedTrips ?></div>
                    <div class="stat-label">Trips Completed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">💰</div>
                <div>
                    <div class="stat-value">KES <?= number_format($totalSpent, 0) ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">🗓️</div>
                <div>
                    <div class="stat-value counter" data-target="<?= $activeBookings ?>"><?= $activeBookings ?></div>
                    <div class="stat-label">Active Bookings</div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">

            <!-- Upcoming Trips -->
            <div class="card">
                <div class="card-header">
                    <h3>🚌 Available Trips</h3>
                    <a href="trips.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if ($upcomingTrips->num_rows === 0): ?>
                        <div class="empty-state"><div class="icon">🚌</div><p>No upcoming trips available</p></div>
                    <?php else: ?>
                        <?php while ($trip = $upcomingTrips->fetch_assoc()): ?>
                        <div style="padding:14px 18px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
                            <div>
                                <div style="font-weight:700;"><?= htmlspecialchars($trip['origin']) ?> → <?= htmlspecialchars($trip['destination']) ?></div>
                                <div style="font-size:0.82rem;color:var(--gray);">
                                    🕐 <?= date('D d M, H:i', strtotime($trip['departure_time'])) ?>
                                    · 💺 <?= $trip['available_seats'] ?> seats left
                                    · 🚌 <?= htmlspecialchars($trip['registration_no']) ?>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:800;color:var(--primary);">KES <?= number_format($trip['fare'], 0) ?></div>
                                <a href="book.php?trip_id=<?= $trip['id'] ?>" class="btn btn-sm btn-primary" style="margin-top:4px;">Book</a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifications -->
            <div class="card">
                <div class="card-header">
                    <h3>🔔 Notifications</h3>
                    <a href="notifications.php" class="btn btn-sm btn-outline">All</a>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if ($notifications->num_rows === 0): ?>
                        <div class="empty-state" style="padding:30px"><div class="icon">🔕</div><p>No notifications</p></div>
                    <?php else: ?>
                        <?php while ($notif = $notifications->fetch_assoc()): ?>
                        <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;<?= !$notif['is_read'] ? 'background:#f8fff8;' : '' ?>">
                            <div style="font-size:0.88rem;font-weight:<?= !$notif['is_read'] ? '700' : '400' ?>;"><?= htmlspecialchars($notif['title']) ?></div>
                            <div style="font-size:0.8rem;color:var(--gray);"><?= timeAgo($notif['created_at']) ?></div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Recent Bookings</h3>
                <a href="my_bookings.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if ($recentBookings->num_rows === 0): ?>
                    <div class="empty-state"><div class="icon">📋</div><h3>No bookings yet</h3><p>Browse trips and make your first booking!</p><a href="trips.php" class="btn btn-primary" style="margin-top:12px;">Browse Trips</a></div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table class="searchable-table">
                        <thead><tr><th>Ref</th><th>Route</th><th>Departure</th><th>Seat</th><th>Booking</th><th>Payment</th></tr></thead>
                        <tbody>
                        <?php while ($b = $recentBookings->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
                            <td><?= htmlspecialchars($b['origin']) ?> → <?= htmlspecialchars($b['destination']) ?></td>
                            <td><?= date('d M, H:i', strtotime($b['departure_time'])) ?></td>
                            <td>Seat <?= $b['seat_number'] ?></td>
                            <td><span class="badge badge-<?= $b['booking_status'] === 'confirmed' ? 'success' : ($b['booking_status'] === 'cancelled' ? 'danger' : 'info') ?>"><?= ucfirst($b['booking_status']) ?></span></td>
                            <td><span class="badge badge-<?= ($b['payment_status'] ?? 'pending') === 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($b['payment_status'] ?? 'pending') ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
