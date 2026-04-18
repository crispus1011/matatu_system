<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $routeId   = intval($_POST['route_id']);
        $vehicleId = intval($_POST['vehicle_id']);
        $driverId  = intval($_POST['driver_id']);
        $depart    = sanitize($_POST['departure_time']);
        $seats     = intval($_POST['total_seats']);
        $status    = sanitize($_POST['status']);

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO trips (route_id,vehicle_id,driver_id,departure_time,available_seats,total_seats,status) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("iiisiis", $routeId, $vehicleId, $driverId, $depart, $seats, $seats, $status);
            if ($stmt->execute()) {
                $msg = 'Trip scheduled.';
                createNotification($driverId, 'New Trip Assigned!', 'You have been assigned a new trip. Check your dashboard.');
            } else { $error = 'Failed to schedule trip.'; }
        } else {
            $id = intval($_POST['trip_id']);
            $stmt = $conn->prepare("UPDATE trips SET route_id=?,vehicle_id=?,driver_id=?,departure_time=?,total_seats=?,status=? WHERE id=?");
            $stmt->bind_param("iiisisi", $routeId, $vehicleId, $driverId, $depart, $seats, $status, $id);
            $stmt->execute() ? $msg = 'Trip updated.' : ($error = 'Update failed.');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['trip_id']);
        $conn->query("DELETE FROM trips WHERE id=$id") ? $msg = 'Trip deleted.' : ($error = 'Cannot delete trip with bookings.');
    }
}

$trips = $conn->query("
    SELECT t.*, r.route_name, r.origin, r.destination, r.fare,
           v.registration_no, u.full_name as driver_name,
           (SELECT COUNT(*) FROM bookings b WHERE b.trip_id=t.id AND b.booking_status='confirmed') as booked_count
    FROM trips t
    JOIN routes r ON t.route_id=r.id
    JOIN vehicles v ON t.vehicle_id=v.id
    JOIN users u ON t.driver_id=u.id
    ORDER BY t.departure_time DESC
");
$routes   = $conn->query("SELECT * FROM routes WHERE status='active' ORDER BY route_name");
$vehicles = $conn->query("SELECT * FROM vehicles WHERE status='active' ORDER BY registration_no");
$drivers  = $conn->query("SELECT id, full_name FROM users WHERE role='driver' AND status='active' ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trips - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "trips"; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">📅 Trip Management</h1><p class="page-subtitle">Schedule and manage all trips</p></div>
            <button class="btn btn-primary" data-modal="tripModal">+ Schedule Trip</button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="search-bar"><input type="text" id="tableSearch" class="form-control" placeholder="🔍 Search trips..."></div>

        <div class="table-wrapper">
            <table class="searchable-table">
                <thead><tr><th>#</th><th>Route</th><th>Departure</th><th>Vehicle</th><th>Driver</th><th>Passengers</th><th>Fare</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; while ($t = $trips->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($t['origin']) ?>→<?= htmlspecialchars($t['destination']) ?></strong><br><small style="color:var(--gray);"><?= htmlspecialchars($t['route_name']) ?></small></td>
                    <td><?= date('d M Y', strtotime($t['departure_time'])) ?><br><small><?= date('H:i', strtotime($t['departure_time'])) ?></small></td>
                    <td><?= htmlspecialchars($t['registration_no']) ?></td>
                    <td><?= htmlspecialchars($t['driver_name']) ?></td>
                    <td><?= $t['booked_count'] ?>/<?= $t['total_seats'] ?></td>
                    <td>KES <?= number_format($t['fare'], 0) ?></td>
                    <td><span class="badge badge-<?= $t['status']==='completed'?'success':($t['status']==='ongoing'?'warning':($t['status']==='cancelled'?'danger':'info')) ?>"><?= ucfirst($t['status']) ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick='editTrip(<?= json_encode($t) ?>)'>Edit</button>
                        <a href="trip_detail.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                        <form method="POST" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="trip_id" value="<?= $t['id'] ?>"><button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this trip?">Del</button></form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Trip Modal -->
<div class="modal-overlay" id="tripModal">
    <div class="modal">
        <div class="modal-header"><h3 class="modal-title" id="tripModalTitle">📅 Schedule Trip</h3><button class="modal-close">✕</button></div>
        <form method="POST">
            <input type="hidden" name="action" id="tripAction" value="add">
            <input type="hidden" name="trip_id" id="tripId">
            <div class="form-group"><label class="form-label">Route *</label>
                <select name="route_id" id="tripRoute" class="form-control" required>
                    <option value="">— Select Route —</option>
                    <?php $routes->data_seek(0); while ($r = $routes->fetch_assoc()): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['route_name']) ?> (KES <?= number_format($r['fare'],0) ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Vehicle *</label>
                    <select name="vehicle_id" id="tripVehicle" class="form-control" required>
                        <option value="">— Select Vehicle —</option>
                        <?php $vehicles->data_seek(0); while ($v = $vehicles->fetch_assoc()): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['registration_no']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Driver *</label>
                    <select name="driver_id" id="tripDriver" class="form-control" required>
                        <option value="">— Select Driver —</option>
                        <?php $drivers->data_seek(0); while ($d = $drivers->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Departure Time *</label><input type="datetime-local" name="departure_time" id="tripDepart" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Total Seats</label><input type="number" name="total_seats" id="tripSeats" class="form-control" value="14" min="1" max="60"></div>
            </div>
            <div class="form-group"><label class="form-label">Status</label><select name="status" id="tripStatus" class="form-control"><option value="scheduled">Scheduled</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
            <button type="submit" class="btn btn-primary btn-block">Save Trip</button>
        </form>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function editTrip(t) {
    document.getElementById('tripModalTitle').textContent = '✏️ Edit Trip';
    document.getElementById('tripAction').value = 'edit';
    document.getElementById('tripId').value = t.id;
    document.getElementById('tripRoute').value = t.route_id;
    document.getElementById('tripVehicle').value = t.vehicle_id;
    document.getElementById('tripDriver').value = t.driver_id;
    document.getElementById('tripDepart').value = t.departure_time.replace(' ','T').substring(0,16);
    document.getElementById('tripSeats').value = t.total_seats;
    document.getElementById('tripStatus').value = t.status;
    openModal('tripModal');
}
</script>
</body>
</html>
