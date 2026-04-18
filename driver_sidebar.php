<?php
require_once '../includes/auth.php';
requireRole('driver', '../login.php');
$userId = $_SESSION['user_id'];

// Stats
$totalTrips    = $conn->query("SELECT COUNT(*) c FROM trips WHERE driver_id=$userId")->fetch_assoc()['c'];
$completed     = $conn->query("SELECT COUNT(*) c FROM trips WHERE driver_id=$userId AND status='completed'")->fetch_assoc()['c'];
$scheduled     = $conn->query("SELECT COUNT(*) c FROM trips WHERE driver_id=$userId AND status='scheduled'")->fetch_assoc()['c'];
$totalPassengers = $conn->query("SELECT COUNT(*) c FROM bookings b JOIN trips t ON b.trip_id=t.id WHERE t.driver_id=$userId AND b.booking_status='confirmed'")->fetch_assoc()['c'];

// Today's trips
$today = date('Y-m-d');
$todayTrips = $conn->query("
    SELECT t.*, r.origin, r.destination, r.route_name, r.fare, v.registration_no,
           (SELECT COUNT(*) FROM bookings b WHERE b.trip_id=t.id AND b.booking_status='confirmed') as booked_count
    FROM trips t
    JOIN routes r ON t.route_id=r.id
    JOIN vehicles v ON t.vehicle_id=v.id
    WHERE t.driver_id=$userId AND DATE(t.departure_time)='$today'
    ORDER BY t.departure_time ASC
");

// Upcoming trips
$upcoming = $conn->query("
    SELECT t.*, r.origin, r.destination, r.route_name, v.registration_no,
           (SELECT COUNT(*) FROM bookings b WHERE b.trip_id=t.id AND b.booking_status='confirmed') as booked_count
    FROM trips t
    JOIN routes r ON t.route_id=r.id
    JOIN vehicles v ON t.vehicle_id=v.id
    WHERE t.driver_id=$userId AND t.departure_time > NOW() AND t.status='scheduled'
    ORDER BY t.departure_time ASC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "dashboard"; include '../includes/driver_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Driver Dashboard 🚌</h1>
                <p class="page-subtitle">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>. Today is <?= date('l, d M Y') ?>.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon green">🚌</div><div><div class="stat-value"><?= $totalTrips ?></div><div class="stat-label">Total Trips</div></div></div>
            <div class="stat-card"><div class="stat-icon blue">✅</div><div><div class="stat-value"><?= $completed ?></div><div class="stat-label">Completed</div></div></div>
            <div class="stat-card"><div class="stat-icon orange">📅</div><div><div class="stat-value"><?= $scheduled ?></div><div class="stat-label">Scheduled</div></div></div>
            <div class="stat-card"><div class="stat-icon red">👥</div><div><div class="stat-value"><?= $totalPassengers ?></div><div class="stat-label">Total Passengers</div></div></div>
        </div>

        <!-- Today's Trips -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>📅 Today's Trips — <?= date('d M Y') ?></h3></div>
            <div class="card-body" style="padding:0;">
                <?php if ($todayTrips->num_rows === 0): ?>
                    <div class="empty-state"><div class="icon">😴</div><p>No trips scheduled for today.</p></div>
                <?php else: while ($trip = $todayTrips->fetch_assoc()): ?>
                <div style="padding:16px 20px;border-bottom:1px solid #f0f0f0;">
                    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <div>
                            <div style="font-weight:800;"><?= htmlspecialchars($trip['origin']) ?> → <?= htmlspecialchars($trip['destination']) ?></div>
                            <div style="font-size:0.82rem;color:var(--gray);">
                                🕐 <?= date('H:i', strtotime($trip['departure_time'])) ?>
                                &nbsp;·&nbsp; 🚌 <?= htmlspecialchars($trip['registration_no']) ?>
                                &nbsp;·&nbsp; 👥 <?= $trip['booked_count'] ?>/<?= $trip['total_seats'] ?> booked
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <span class="badge badge-<?= $trip['status']==='ongoing'?'warning':($trip['status']==='completed'?'success':'info') ?>"><?= ucfirst($trip['status']) ?></span>
                            <?php if ($trip['status'] === 'scheduled'): ?>
                            <a href="update_trip.php?id=<?= $trip['id'] ?>&status=ongoing" class="btn btn-sm btn-secondary" data-confirm="Start this trip?">▶ Start Trip</a>
                            <?php elseif ($trip['status'] === 'ongoing'): ?>
                            <a href="update_trip.php?id=<?= $trip['id'] ?>&status=completed" class="btn btn-sm btn-primary" data-confirm="Mark trip as completed?">✅ Complete</a>
                            <?php endif; ?>
                            <a href="trip_manifest.php?id=<?= $trip['id'] ?>" class="btn btn-sm btn-outline">👥 Passengers</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>

        <!-- Upcoming Trips -->
        <div class="card">
            <div class="card-header"><h3>🗓️ Upcoming Trips</h3><a href="my_trips.php" class="btn btn-sm btn-outline">View All</a></div>
            <div class="card-body" style="padding:0;">
                <?php if ($upcoming->num_rows === 0): ?>
                    <div class="empty-state"><div class="icon">📅</div><p>No upcoming trips assigned.</p></div>
                <?php else: while ($trip = $upcoming->fetch_assoc()): ?>
                <div style="padding:12px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:700;"><?= htmlspecialchars($trip['origin']) ?> → <?= htmlspecialchars($trip['destination']) ?></div>
                        <div style="font-size:0.82rem;color:var(--gray);">🕐 <?= date('D d M, H:i', strtotime($trip['departure_time'])) ?> &nbsp;·&nbsp; 👥 <?= $trip['booked_count'] ?> booked</div>
                    </div>
                    <a href="trip_manifest.php?id=<?= $trip['id'] ?>" class="btn btn-sm btn-outline">View</a>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
