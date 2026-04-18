<?php
require_once '../includes/auth.php';
requireRole('driver', '../login.php');
$userId = $_SESSION['user_id'];
$tripId = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT t.*, r.origin, r.destination, r.route_name, v.registration_no FROM trips t JOIN routes r ON t.route_id=r.id JOIN vehicles v ON t.vehicle_id=v.id WHERE t.id=? AND t.driver_id=?");
$stmt->bind_param("ii", $tripId, $userId);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();
if (!$trip) { header("Location: my_trips.php"); exit(); }

$passengers = $conn->query("
    SELECT b.seat_number, b.booking_ref, b.booking_status, u.full_name, u.phone, p.payment_method, p.payment_status
    FROM bookings b
    JOIN users u ON b.passenger_id = u.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.trip_id = $tripId
    ORDER BY b.seat_number ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Manifest - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>@media print { .no-print{display:none!important;} }</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="main-content" style="max-width:800px;margin:0 auto;padding:28px 20px;">
    <div class="page-header no-print">
        <div><h1 class="page-title">👥 Trip Manifest</h1><p class="page-subtitle"><?= htmlspecialchars($trip['origin']) ?> → <?= htmlspecialchars($trip['destination']) ?></p></div>
        <div style="display:flex;gap:10px;">
            <button onclick="window.print()" class="btn btn-outline">🖨️ Print</button>
            <a href="my_trips.php" class="btn btn-primary">← Back</a>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;font-size:0.88rem;">
                <div><span style="color:var(--gray);">Route</span><br><strong><?= htmlspecialchars($trip['route_name']) ?></strong></div>
                <div><span style="color:var(--gray);">Departure</span><br><strong><?= date('D d M Y, H:i', strtotime($trip['departure_time'])) ?></strong></div>
                <div><span style="color:var(--gray);">Vehicle</span><br><strong><?= htmlspecialchars($trip['registration_no']) ?></strong></div>
                <div><span style="color:var(--gray);">Status</span><br><span class="badge badge-<?= $trip['status']==='completed'?'success':($trip['status']==='ongoing'?'warning':'info') ?>"><?= ucfirst($trip['status']) ?></span></div>
                <div><span style="color:var(--gray);">Booked Seats</span><br><strong><?= $passengers->num_rows ?>/<?= $trip['total_seats'] ?></strong></div>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead><tr><th>Seat</th><th>Passenger</th><th>Phone</th><th>Ref</th><th>Payment</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($passengers->num_rows === 0): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray);">No passengers booked yet.</td></tr>
            <?php else: while ($p = $passengers->fetch_assoc()): ?>
            <tr>
                <td><strong><?= $p['seat_number'] ?></strong></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                <td><small><?= htmlspecialchars($p['booking_ref']) ?></small></td>
                <td><?= ucfirst($p['payment_method'] ?? '—') ?></td>
                <td><span class="badge badge-<?= $p['payment_status']==='completed'?'success':'warning' ?>"><?= ucfirst($p['payment_status'] ?? 'pending') ?></span></td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
