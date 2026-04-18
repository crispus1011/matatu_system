<?php
require_once '../includes/auth.php';
requireRole('driver', '../login.php');
$userId = $_SESSION['user_id'];

$filter = sanitize($_GET['filter'] ?? 'all');
$where  = "t.driver_id = $userId";
if ($filter === 'scheduled')  $where .= " AND t.status='scheduled'";
elseif ($filter === 'ongoing')   $where .= " AND t.status='ongoing'";
elseif ($filter === 'completed') $where .= " AND t.status='completed'";

$trips = $conn->query("
    SELECT t.*, r.origin, r.destination, r.route_name, r.fare, v.registration_no,
           (SELECT COUNT(*) FROM bookings b WHERE b.trip_id=t.id AND b.booking_status='confirmed') as booked_count
    FROM trips t
    JOIN routes r ON t.route_id=r.id
    JOIN vehicles v ON t.vehicle_id=v.id
    WHERE $where
    ORDER BY t.departure_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trips - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "my_trips"; include '../includes/driver_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">My Trips</h1><p class="page-subtitle">All your assigned trips</p></div>
        </div>

        <div class="tabs" style="margin-bottom:20px;">
            <a href="?filter=all" class="tab-btn <?= $filter==='all'?'active':'' ?>" style="border:none;">All</a>
            <a href="?filter=scheduled" class="tab-btn <?= $filter==='scheduled'?'active':'' ?>" style="border:none;">Scheduled</a>
            <a href="?filter=ongoing" class="tab-btn <?= $filter==='ongoing'?'active':'' ?>" style="border:none;">Ongoing</a>
            <a href="?filter=completed" class="tab-btn <?= $filter==='completed'?'active':'' ?>" style="border:none;">Completed</a>
        </div>

        <?php if ($trips->num_rows === 0): ?>
        <div class="card"><div class="card-body"><div class="empty-state"><div class="icon">🚌</div><p>No trips found.</p></div></div></div>
        <?php else: while ($trip = $trips->fetch_assoc()): ?>
        <div class="trip-card" style="cursor:default;">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="font-weight:800;font-size:1rem;"><?= htmlspecialchars($trip['origin']) ?> → <?= htmlspecialchars($trip['destination']) ?></div>
                    <div style="font-size:0.82rem;color:var(--gray);margin-top:4px;">
                        🕐 <?= date('D d M Y, H:i', strtotime($trip['departure_time'])) ?>
                        &nbsp;·&nbsp; 🚌 <?= htmlspecialchars($trip['registration_no']) ?>
                        &nbsp;·&nbsp; 👥 <?= $trip['booked_count'] ?>/<?= $trip['total_seats'] ?> passengers
                        &nbsp;·&nbsp; 💰 KES <?= number_format($trip['fare'], 0) ?>/seat
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <span class="badge badge-<?= $trip['status']==='completed'?'success':($trip['status']==='ongoing'?'warning':'info') ?>"><?= ucfirst($trip['status']) ?></span>
                    <?php if ($trip['status'] === 'scheduled'): ?>
                        <a href="update_trip.php?id=<?= $trip['id'] ?>&status=ongoing" class="btn btn-sm btn-secondary" data-confirm="Start trip now?">▶ Start</a>
                    <?php elseif ($trip['status'] === 'ongoing'): ?>
                        <a href="update_trip.php?id=<?= $trip['id'] ?>&status=completed" class="btn btn-sm btn-primary" data-confirm="Complete trip?">✅ Complete</a>
                    <?php endif; ?>
                    <a href="trip_manifest.php?id=<?= $trip['id'] ?>" class="btn btn-sm btn-outline">👥 Manifest</a>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
