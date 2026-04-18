<?php
require_once '../includes/auth.php';
requireRole('driver', '../login.php');
$userId = $_SESSION['user_id'];

$passengers = $conn->query("
    SELECT u.full_name, u.email, u.phone, b.seat_number, b.booking_ref,
           r.origin, r.destination, t.departure_time, p.payment_method, p.payment_status
    FROM bookings b
    JOIN users u ON b.passenger_id=u.id
    JOIN trips t ON b.trip_id=t.id
    JOIN routes r ON t.route_id=r.id
    LEFT JOIN payments p ON p.booking_id=b.id
    WHERE t.driver_id=$userId AND b.booking_status='confirmed'
    ORDER BY t.departure_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passengers - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "passengers"; include '../includes/driver_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">👥 My Passengers</h1><p class="page-subtitle">All passengers booked on your trips</p></div>
        </div>
        <div class="search-bar">
            <input type="text" id="tableSearch" class="form-control" placeholder="🔍 Search passengers...">
        </div>
        <div class="table-wrapper">
            <table class="searchable-table">
                <thead><tr><th>Passenger</th><th>Phone</th><th>Route</th><th>Departure</th><th>Seat</th><th>Payment</th><th>Ref</th></tr></thead>
                <tbody>
                <?php if ($passengers->num_rows === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray);">No passengers found.</td></tr>
                <?php else: while ($p = $passengers->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['full_name']) ?></strong><br><small style="color:var(--gray);"><?= htmlspecialchars($p['email']) ?></small></td>
                    <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['origin']) ?> → <?= htmlspecialchars($p['destination']) ?></td>
                    <td><?= date('d M, H:i', strtotime($p['departure_time'])) ?></td>
                    <td>Seat <?= $p['seat_number'] ?></td>
                    <td><?= ucfirst($p['payment_method'] ?? '—') ?><br><span class="badge badge-<?= $p['payment_status']==='completed'?'success':'warning' ?>"><?= ucfirst($p['payment_status'] ?? 'pending') ?></span></td>
                    <td><small><?= htmlspecialchars($p['booking_ref']) ?></small></td>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
