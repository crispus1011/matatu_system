<?php
require_once '../includes/auth.php';
requireRole('passenger', '../login.php');
$ref = sanitize($_GET['ref'] ?? '');
if (!$ref) { header("Location: my_bookings.php"); exit(); }

$stmt = $conn->prepare("
    SELECT b.*, r.origin, r.destination, r.route_name, r.fare,
           t.departure_time, v.registration_no, u.full_name as driver_name,
           p.payment_method, p.payment_status, p.mpesa_code
    FROM bookings b
    JOIN trips t ON b.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    JOIN vehicles v ON t.vehicle_id = v.id
    JOIN users u ON t.driver_id = u.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.booking_ref = ? AND b.passenger_id = ?
");
$stmt->bind_param("si", $ref, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if (!$booking) { header("Location: my_bookings.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .success-icon { font-size: 5rem; text-align: center; animation: bounceIn 0.6s ease; }
        @keyframes bounceIn { 0%{transform:scale(0)} 60%{transform:scale(1.1)} 100%{transform:scale(1)} }
        .ticket { border: 2px dashed var(--primary); border-radius: var(--radius); padding: 24px; margin: 20px 0; }
        .ticket-row { display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e0e0e0;font-size:0.9rem; }
        @media print { .no-print { display:none!important; } body { background:white; } }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="main-content" style="max-width:580px;margin:0 auto;padding:40px 20px;">
    <div style="text-align:center;margin-bottom:24px;">
        <div class="success-icon">🎉</div>
        <h1 style="font-size:1.8rem;font-weight:800;color:var(--primary);margin:12px 0 6px;">Booking Confirmed!</h1>
        <p style="color:var(--gray);">Your seat has been reserved successfully.</p>
    </div>

    <div class="ticket">
        <div style="text-align:center;margin-bottom:16px;">
            <div style="font-size:1.5rem;font-weight:800;color:var(--primary);"><?= htmlspecialchars($booking['booking_ref']) ?></div>
            <div style="font-size:0.8rem;color:var(--gray);">Booking Reference</div>
        </div>

        <div class="ticket-row"><span>📍 From</span><strong><?= htmlspecialchars($booking['origin']) ?></strong></div>
        <div class="ticket-row"><span>🏁 To</span><strong><?= htmlspecialchars($booking['destination']) ?></strong></div>
        <div class="ticket-row"><span>🗺️ Route</span><strong><?= htmlspecialchars($booking['route_name']) ?></strong></div>
        <div class="ticket-row"><span>🕐 Departure</span><strong><?= date('D d M Y, H:i', strtotime($booking['departure_time'])) ?></strong></div>
        <div class="ticket-row"><span>💺 Seat Number</span><strong>Seat <?= $booking['seat_number'] ?></strong></div>
        <div class="ticket-row"><span>🚌 Vehicle</span><strong><?= htmlspecialchars($booking['registration_no']) ?></strong></div>
        <div class="ticket-row"><span>👤 Driver</span><strong><?= htmlspecialchars($booking['driver_name']) ?></strong></div>
        <div class="ticket-row"><span>💳 Payment</span>
            <strong><?= ucfirst($booking['payment_method']) ?>
                <span class="badge badge-<?= $booking['payment_status'] === 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($booking['payment_status']) ?></span>
            </strong>
        </div>
        <div class="ticket-row" style="border:none;"><span>💰 Amount</span><strong style="color:var(--primary);font-size:1.1rem;">KES <?= number_format($booking['fare'], 2) ?></strong></div>
    </div>

    <?php if ($booking['payment_method'] === 'cash'): ?>
    <div class="alert alert-warning">
        💵 <strong>Cash Payment:</strong> Please have KES <?= number_format($booking['fare'], 2) ?> ready to pay the driver when boarding.
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:12px;flex-wrap:wrap;" class="no-print">
        <button onclick="window.print()" class="btn btn-outline" style="flex:1;">🖨️ Print Ticket</button>
        <a href="my_bookings.php" class="btn btn-primary" style="flex:1;">📋 My Bookings</a>
        <a href="trips.php" class="btn btn-secondary" style="flex:1;">🚌 Book Another</a>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
