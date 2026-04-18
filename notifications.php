<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

// Stats
$totalUsers    = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$totalPassengers = $conn->query("SELECT COUNT(*) c FROM users WHERE role='passenger'")->fetch_assoc()['c'];
$totalDrivers  = $conn->query("SELECT COUNT(*) c FROM users WHERE role='driver'")->fetch_assoc()['c'];
$totalTrips    = $conn->query("SELECT COUNT(*) c FROM trips")->fetch_assoc()['c'];
$totalBookings = $conn->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'];
$totalRevenue  = $conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE payment_status='completed'")->fetch_assoc()['s'];
$activeRoutes  = $conn->query("SELECT COUNT(*) c FROM routes WHERE status='active'")->fetch_assoc()['c'];
$totalVehicles = $conn->query("SELECT COUNT(*) c FROM vehicles WHERE status='active'")->fetch_assoc()['c'];

// Recent bookings
$recentBookings = $conn->query("
    SELECT b.*, u.full_name, r.origin, r.destination, t.departure_time, p.payment_status
    FROM bookings b
    JOIN users u ON b.passenger_id=u.id
    JOIN trips t ON b.trip_id=t.id
    JOIN routes r ON t.route_id=r.id
    LEFT JOIN payments p ON p.booking_id=b.id
    ORDER BY b.created_at DESC LIMIT 8
");

// Revenue last 7 days for chart
$revenueData = [];
$revenueLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime($date));
    $rev = $conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE DATE(paid_at)='$date' AND payment_status='completed'")->fetch_assoc()['s'];
    $revenueLabels[] = $label;
    $revenueData[] = $rev;
}

// Bookings by route
$routeStats = $conn->query("
    SELECT r.route_name, COUNT(b.id) as total_bookings
    FROM routes r LEFT JOIN trips t ON r.id=t.route_id LEFT JOIN bookings b ON t.id=b.trip_id
    GROUP BY r.id ORDER BY total_bookings DESC LIMIT 5
");
$routeNames = []; $routeCounts = [];
while ($rs = $routeStats->fetch_assoc()) { $routeNames[] = $rs['route_name']; $routeCounts[] = $rs['total_bookings']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <!-- Admin Sidebar -->
    <?php $currentPage = "dashboard"; include '../includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">Admin Dashboard 📊</h1><p class="page-subtitle">System overview — <?= date('l, d M Y') ?></p></div>
        </div>

        <!-- Stats Row 1 -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon green">👥</div><div><div class="stat-value counter" data-target="<?= $totalUsers ?>"><?= $totalUsers ?></div><div class="stat-label">Total Users</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">📋</div><div><div class="stat-value counter" data-target="<?= $totalBookings ?>"><?= $totalBookings ?></div><div class="stat-label">Total Bookings</div></div></div>
            <div class="stat-card"><div class="stat-icon orange">💰</div><div><div class="stat-value">KES <?= number_format($totalRevenue, 0) ?></div><div class="stat-label">Revenue Collected</div></div></div>
            <div class="stat-card"><div class="stat-icon red">🚌</div><div><div class="stat-value counter" data-target="<?= $totalTrips ?>"><?= $totalTrips ?></div><div class="stat-label">Total Trips</div></div></div>
        </div>
        <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
            <div class="stat-card"><div class="stat-icon green">🧑‍🤝‍🧑</div><div><div class="stat-value"><?= $totalPassengers ?></div><div class="stat-label">Passengers</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">🧑‍✈️</div><div><div class="stat-value"><?= $totalDrivers ?></div><div class="stat-label">Drivers</div></div></div>
            <div class="stat-card"><div class="stat-icon orange">🗺️</div><div><div class="stat-value"><?= $activeRoutes ?></div><div class="stat-label">Active Routes</div></div></div>
            <div class="stat-card"><div class="stat-icon red">🚐</div><div><div class="stat-value"><?= $totalVehicles ?></div><div class="stat-label">Active Vehicles</div></div></div>
        </div>

        <!-- Charts -->
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">
            <div class="card">
                <div class="card-header"><h3>📈 Revenue (Last 7 Days)</h3></div>
                <div class="card-body"><canvas id="revenueChart" height="90"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><h3>🗺️ Bookings by Route</h3></div>
                <div class="card-body"><canvas id="routeChart" height="160"></canvas></div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Recent Bookings</h3>
                <a href="bookings.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Ref</th><th>Passenger</th><th>Route</th><th>Departure</th><th>Booking</th><th>Payment</th></tr></thead>
                        <tbody>
                        <?php while ($b = $recentBookings->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                            <td><?= htmlspecialchars($b['origin']) ?> → <?= htmlspecialchars($b['destination']) ?></td>
                            <td><?= date('d M, H:i', strtotime($b['departure_time'])) ?></td>
                            <td><span class="badge badge-<?= $b['booking_status']==='confirmed'?'success':($b['booking_status']==='cancelled'?'danger':'info') ?>"><?= ucfirst($b['booking_status']) ?></span></td>
                            <td><span class="badge badge-<?= ($b['payment_status']??'pending')==='completed'?'success':'warning' ?>"><?= ucfirst($b['payment_status'] ?? 'pending') ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
<script>
renderLineChart('revenueChart', <?= json_encode($revenueLabels) ?>, <?= json_encode($revenueData) ?>, 'Revenue (KES)');
renderDoughnutChart('routeChart', <?= json_encode($routeNames) ?>, <?= json_encode($routeCounts) ?>);
</script>
</body>
</html>
