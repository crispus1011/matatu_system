<?php
require_once '../includes/auth.php';
requireRole('driver', '../login.php');
$userId = $_SESSION['user_id'];

// Earnings summary
$totalEarnings = $conn->query("
    SELECT IFNULL(SUM(p.amount), 0) s
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN trips t ON b.trip_id = t.id
    WHERE t.driver_id = $userId AND p.payment_status = 'completed'
")->fetch_assoc()['s'];

$monthEarnings = $conn->query("
    SELECT IFNULL(SUM(p.amount), 0) s
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN trips t ON b.trip_id = t.id
    WHERE t.driver_id = $userId
      AND p.payment_status = 'completed'
      AND MONTH(p.paid_at) = MONTH(NOW())
      AND YEAR(p.paid_at) = YEAR(NOW())
")->fetch_assoc()['s'];

$completedTrips = $conn->query("SELECT COUNT(*) c FROM trips WHERE driver_id=$userId AND status='completed'")->fetch_assoc()['c'];
$totalPassengers = $conn->query("
    SELECT COUNT(*) c FROM bookings b
    JOIN trips t ON b.trip_id=t.id
    WHERE t.driver_id=$userId AND b.booking_status='confirmed'
")->fetch_assoc()['c'];

// Monthly breakdown (last 6 months)
$monthly = $conn->query("
    SELECT DATE_FORMAT(p.paid_at, '%b %Y') as month,
           DATE_FORMAT(p.paid_at, '%Y-%m') as sort_key,
           SUM(p.amount) as revenue,
           COUNT(DISTINCT t.id) as trips
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN trips t ON b.trip_id = t.id
    WHERE t.driver_id = $userId AND p.payment_status = 'completed'
    GROUP BY DATE_FORMAT(p.paid_at, '%Y-%m')
    ORDER BY sort_key DESC
    LIMIT 6
");
$months = []; $revenues = []; $tripCounts = [];
$rows = [];
while ($r = $monthly->fetch_assoc()) {
    $months[]    = $r['month'];
    $revenues[]  = (float)$r['revenue'];
    $tripCounts[]= (int)$r['trips'];
    $rows[]      = $r;
}
$months   = array_reverse($months);
$revenues = array_reverse($revenues);

// Recent completed trips with earnings
$recentTrips = $conn->query("
    SELECT t.id, t.departure_time, r.origin, r.destination,
           COUNT(b.id) as passengers,
           SUM(p.amount) as trip_revenue
    FROM trips t
    JOIN routes r ON t.route_id = r.id
    LEFT JOIN bookings b ON b.trip_id = t.id AND b.booking_status = 'confirmed'
    LEFT JOIN payments p ON p.booking_id = b.id AND p.payment_status = 'completed'
    WHERE t.driver_id = $userId AND t.status = 'completed'
    GROUP BY t.id
    ORDER BY t.departure_time DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Earnings - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = 'earnings'; include '../includes/driver_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">💰 My Earnings</h1>
                <p class="page-subtitle">Revenue summary from completed trips</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">💰</div>
                <div><div class="stat-value">KES <?= number_format($totalEarnings, 0) ?></div><div class="stat-label">Total Earnings</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">📅</div>
                <div><div class="stat-value">KES <?= number_format($monthEarnings, 0) ?></div><div class="stat-label">This Month</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">✅</div>
                <div><div class="stat-value"><?= $completedTrips ?></div><div class="stat-label">Trips Completed</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">👥</div>
                <div><div class="stat-value"><?= $totalPassengers ?></div><div class="stat-label">Passengers Served</div></div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <?php if (!empty($months)): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>📈 Monthly Revenue Trend</h3></div>
            <div class="card-body"><canvas id="earningsChart" height="70"></canvas></div>
        </div>
        <?php endif; ?>

        <!-- Monthly Breakdown Table -->
        <?php if (!empty($rows)): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>📊 Monthly Breakdown</h3></div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Month</th><th>Trips</th><th>Revenue (KES)</th><th>Avg per Trip</th></tr></thead>
                    <tbody>
                    <?php foreach (array_reverse($rows) as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['month']) ?></strong></td>
                        <td><?= $r['trips'] ?></td>
                        <td><strong>KES <?= number_format($r['revenue'], 0) ?></strong></td>
                        <td>KES <?= $r['trips'] > 0 ? number_format($r['revenue'] / $r['trips'], 0) : 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent trips -->
        <div class="card">
            <div class="card-header"><h3>🚌 Recent Completed Trips</h3></div>
            <div class="card-body" style="padding:0;">
                <?php if ($recentTrips->num_rows === 0): ?>
                <div class="empty-state"><div class="icon">🚌</div><p>No completed trips yet.</p></div>
                <?php else: while ($t = $recentTrips->fetch_assoc()): ?>
                <div style="padding:12px 18px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <div>
                        <div style="font-weight:700;"><?= htmlspecialchars($t['origin']) ?> → <?= htmlspecialchars($t['destination']) ?></div>
                        <div style="font-size:0.82rem;color:var(--gray);">
                            🕐 <?= date('D d M Y, H:i', strtotime($t['departure_time'])) ?>
                            &nbsp;·&nbsp; 👥 <?= $t['passengers'] ?> passengers
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:800;color:var(--primary);font-size:1rem;">
                            KES <?= number_format($t['trip_revenue'] ?? 0, 0) ?>
                        </div>
                        <div style="font-size:0.78rem;color:var(--gray);">collected</div>
                    </div>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
<script>
renderLineChart('earningsChart', <?= json_encode($months) ?>, <?= json_encode($revenues) ?>, 'Revenue (KES)');
</script>
</body>
</html>
