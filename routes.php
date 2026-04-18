<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$total    = $conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE payment_status='completed'")->fetch_assoc()['s'];
$pending  = $conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE payment_status='pending'")->fetch_assoc()['s'];
$refunded = $conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE payment_status='refunded'")->fetch_assoc()['s'];
$mpesa    = $conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE payment_method='mpesa' AND payment_status='completed'")->fetch_assoc()['s'];

$methodFilter = sanitize($_GET['method'] ?? 'all');
$where = $methodFilter !== 'all' ? "WHERE p.payment_method='$methodFilter'" : '';

$payments = $conn->query("
    SELECT p.*, u.full_name, b.booking_ref, r.origin, r.destination
    FROM payments p
    JOIN users u ON p.passenger_id=u.id
    JOIN bookings b ON p.booking_id=b.id
    JOIN trips t ON b.trip_id=t.id
    JOIN routes r ON t.route_id=r.id
    $where
    ORDER BY p.paid_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "payments"; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">💳 Payment Management</h1><p class="page-subtitle">Financial overview and transaction records</p></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon green">💰</div><div><div class="stat-value">KES <?= number_format($total,0) ?></div><div class="stat-label">Total Collected</div></div></div>
            <div class="stat-card"><div class="stat-icon orange">⏳</div><div><div class="stat-value">KES <?= number_format($pending,0) ?></div><div class="stat-label">Pending</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">📱</div><div><div class="stat-value">KES <?= number_format($mpesa,0) ?></div><div class="stat-label">M-Pesa Revenue</div></div></div>
            <div class="stat-card"><div class="stat-icon red">↩️</div><div><div class="stat-value">KES <?= number_format($refunded,0) ?></div><div class="stat-label">Refunded</div></div></div>
        </div>

        <div class="tabs" style="margin-bottom:16px;">
            <a href="?method=all" class="tab-btn <?= $methodFilter==='all'?'active':'' ?>" style="border:none;">All</a>
            <a href="?method=cash" class="tab-btn <?= $methodFilter==='cash'?'active':'' ?>" style="border:none;">Cash</a>
            <a href="?method=mpesa" class="tab-btn <?= $methodFilter==='mpesa'?'active':'' ?>" style="border:none;">M-Pesa</a>
            <a href="?method=card" class="tab-btn <?= $methodFilter==='card'?'active':'' ?>" style="border:none;">Card</a>
        </div>

        <div class="search-bar"><input type="text" id="tableSearch" class="form-control" placeholder="🔍 Search payments..."></div>

        <div class="table-wrapper">
            <table class="searchable-table">
                <thead><tr><th>#</th><th>Booking Ref</th><th>Passenger</th><th>Route</th><th>Method</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php $i=1; while ($p = $payments->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($p['booking_ref']) ?></strong></td>
                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                    <td><?= htmlspecialchars($p['origin']) ?>→<?= htmlspecialchars($p['destination']) ?></td>
                    <td><?= ucfirst($p['payment_method']) ?><?= $p['mpesa_code']?'<br><small style="color:var(--gray);">'.$p['mpesa_code'].'</small>':'' ?></td>
                    <td><strong>KES <?= number_format($p['amount'],2) ?></strong></td>
                    <td><?= date('d M Y, H:i', strtotime($p['paid_at'])) ?></td>
                    <td><span class="badge badge-<?= $p['payment_status']==='completed'?'success':($p['payment_status']==='refunded'?'info':'warning') ?>"><?= ucfirst($p['payment_status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
