<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

// Date range filter
$from = sanitize($_GET['from'] ?? date('Y-m-01'));
$to   = sanitize($_GET['to']   ?? date('Y-m-d'));

// Summary stats
$totalRevenue    = $conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE payment_status='completed' AND DATE(paid_at) BETWEEN '$from' AND '$to'")->fetch_assoc()['s'];
$totalBookings   = $conn->query("SELECT COUNT(*) c FROM bookings WHERE DATE(created_at) BETWEEN '$from' AND '$to'")->fetch_assoc()['c'];
$cancelledCount  = $conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='cancelled' AND DATE(created_at) BETWEEN '$from' AND '$to'")->fetch_assoc()['c'];
$newPassengers   = $conn->query("SELECT COUNT(*) c FROM users WHERE role='passenger' AND DATE(created_at) BETWEEN '$from' AND '$to'")->fetch_assoc()['c'];

// Daily revenue for chart
$dailyRevenue = $conn->query("
    SELECT DATE(paid_at) as day, SUM(amount) as revenue
    FROM payments WHERE payment_status='completed' AND DATE(paid_at) BETWEEN '$from' AND '$to'
    GROUP BY DATE(paid_at) ORDER BY day ASC
");
$chartDays = []; $chartRevenue = [];
while ($d = $dailyRevenue->fetch_assoc()) { $chartDays[] = date('d M', strtotime($d['day'])); $chartRevenue[] = $d['revenue']; }

// Route performance
$routePerf = $conn->query("
    SELECT r.route_name, COUNT(b.id) as bookings, SUM(p.amount) as revenue
    FROM routes r
    LEFT JOIN trips t ON r.id=t.route_id
    LEFT JOIN bookings b ON t.id=b.trip_id AND b.booking_status != 'cancelled'
    LEFT JOIN payments p ON p.booking_id=b.id AND p.payment_status='completed'
    GROUP BY r.id ORDER BY bookings DESC LIMIT 8
");
$rNames=[]; $rBookings=[]; $rRevenue=[];
while ($r = $routePerf->fetch_assoc()) { $rNames[]=$r['route_name']; $rBookings[]=$r['bookings']; $rRevenue[]=$r['revenue']??0; }

// Payment methods
$methodBreak = $conn->query("SELECT payment_method, COUNT(*) c, SUM(amount) s FROM payments WHERE payment_status='completed' GROUP BY payment_method");
$mNames=[]; $mCounts=[]; $mRevenue=[];
while ($m = $methodBreak->fetch_assoc()) { $mNames[]=$m['payment_method']; $mCounts[]=$m['c']; $mRevenue[]=$m['s']; }

// Top passengers
$topPassengers = $conn->query("
    SELECT u.full_name, COUNT(b.id) as trips, SUM(p.amount) as spent
    FROM users u
    JOIN bookings b ON u.id=b.passenger_id
    LEFT JOIN payments p ON p.booking_id=b.id AND p.payment_status='completed'
    WHERE b.booking_status='confirmed' OR b.booking_status='completed'
    GROUP BY u.id ORDER BY trips DESC LIMIT 5
");

// Feedback average
$avgRating = $conn->query("SELECT ROUND(AVG(rating),1) r FROM feedback")->fetch_assoc()['r'];
$feedbackCount = $conn->query("SELECT COUNT(*) c FROM feedback")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>@media print { .no-print{display:none!important;} }</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "reports"; include '../includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">📈 Reports & Analytics</h1><p class="page-subtitle">Operational performance insights</p></div>
            <button onclick="window.print()" class="btn btn-outline no-print">🖨️ Print Report</button>
        </div>

        <!-- Date Filter -->
        <div class="card no-print" style="margin-bottom:20px;">
            <div class="card-body">
                <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="form-group" style="margin:0;"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= $from ?>"></div>
                    <div class="form-group" style="margin:0;"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= $to ?>"></div>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    <a href="reports.php" class="btn btn-outline">Reset</a>
                </form>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon green">💰</div><div><div class="stat-value">KES <?= number_format($totalRevenue,0) ?></div><div class="stat-label">Revenue in Period</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">📋</div><div><div class="stat-value"><?= $totalBookings ?></div><div class="stat-label">New Bookings</div></div></div>
            <div class="stat-card"><div class="stat-icon red">❌</div><div><div class="stat-value"><?= $cancelledCount ?></div><div class="stat-label">Cancellations</div></div></div>
            <div class="stat-card"><div class="stat-icon orange">⭐</div><div><div class="stat-value"><?= $avgRating ?? 'N/A' ?>/5</div><div class="stat-label">Avg Rating (<?= $feedbackCount ?> reviews)</div></div></div>
        </div>

        <!-- Charts Row -->
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">
            <div class="card">
                <div class="card-header"><h3>📈 Daily Revenue</h3></div>
                <div class="card-body"><canvas id="dailyChart" height="90"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><h3>💳 Payment Methods</h3></div>
                <div class="card-body"><canvas id="methodChart" height="150"></canvas></div>
            </div>
        </div>

        <!-- Route Performance + Top Passengers -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            <div class="card">
                <div class="card-header"><h3>🗺️ Route Performance</h3></div>
                <div class="card-body"><canvas id="routeChart" height="150"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><h3>🏆 Top Passengers</h3></div>
                <div class="card-body" style="padding:0;">
                    <table>
                        <thead><tr><th>#</th><th>Passenger</th><th>Trips</th><th>Total Spent</th></tr></thead>
                        <tbody>
                        <?php $i=1; while ($tp = $topPassengers->fetch_assoc()): ?>
                        <tr><td><?= $i++ ?></td><td><?= htmlspecialchars($tp['full_name']) ?></td><td><?= $tp['trips'] ?></td><td>KES <?= number_format($tp['spent']??0, 0) ?></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Route Revenue Table -->
        <div class="card">
            <div class="card-header"><h3>📊 Route Revenue Breakdown</h3></div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Route</th><th>Bookings</th><th>Revenue (KES)</th><th>Performance</th></tr></thead>
                    <tbody>
                    <?php
                    $maxBookings = max($rBookings ?: [1]);
                    foreach ($rNames as $idx => $rn):
                        $pct = $maxBookings > 0 ? ($rBookings[$idx]/$maxBookings)*100 : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($rn) ?></td>
                        <td><?= $rBookings[$idx] ?></td>
                        <td>KES <?= number_format($rRevenue[$idx],0) ?></td>
                        <td><div style="background:#eee;border-radius:4px;height:8px;width:150px;"><div style="background:var(--primary);height:100%;width:<?= $pct ?>%;border-radius:4px;"></div></div></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
<script>
renderLineChart('dailyChart', <?= json_encode($chartDays) ?>, <?= json_encode($chartRevenue) ?>, 'Daily Revenue (KES)');
renderDoughnutChart('methodChart', <?= json_encode($mNames) ?>, <?= json_encode($mCounts) ?>);
renderBarChart('routeChart', <?= json_encode($rNames) ?>, <?= json_encode($rBookings) ?>, 'Bookings per Route');
</script>
</body>
</html>
