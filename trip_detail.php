<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name   = sanitize($_POST['route_name']);
        $origin = sanitize($_POST['origin']);
        $dest   = sanitize($_POST['destination']);
        $dist   = floatval($_POST['distance_km']);
        $fare   = floatval($_POST['fare']);
        $status = sanitize($_POST['status']);

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO routes (route_name,origin,destination,distance_km,fare,status) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("sssdds", $name, $origin, $dest, $dist, $fare, $status);
            $stmt->execute() ? $msg = 'Route added.' : ($error = 'Failed to add route.');
        } else {
            $id = intval($_POST['route_id']);
            $stmt = $conn->prepare("UPDATE routes SET route_name=?,origin=?,destination=?,distance_km=?,fare=?,status=? WHERE id=?");
            $stmt->bind_param("sssddsi", $name, $origin, $dest, $dist, $fare, $status, $id);
            $stmt->execute() ? $msg = 'Route updated.' : ($error = 'Failed to update route.');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['route_id']);
        $conn->query("DELETE FROM routes WHERE id=$id") ? $msg = 'Route deleted.' : ($error = 'Cannot delete (trips may reference it).');
    }
}

$routes = $conn->query("SELECT r.*, COUNT(DISTINCT t.id) as total_trips FROM routes r LEFT JOIN trips t ON r.id=t.route_id GROUP BY r.id ORDER BY r.route_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routes - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "routes"; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">🗺️ Route Management</h1><p class="page-subtitle">Manage all transport routes and fares</p></div>
            <button class="btn btn-primary" data-modal="addRouteModal">+ Add Route</button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Route Name</th><th>Origin</th><th>Destination</th><th>Distance</th><th>Fare (KES)</th><th>Trips</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; $routes->data_seek(0); while ($r = $routes->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($r['route_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['origin']) ?></td>
                    <td><?= htmlspecialchars($r['destination']) ?></td>
                    <td><?= $r['distance_km'] ?> km</td>
                    <td><strong><?= number_format($r['fare'], 2) ?></strong></td>
                    <td><?= $r['total_trips'] ?></td>
                    <td><span class="badge badge-<?= $r['status']==='active'?'success':'secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick='editRoute(<?= json_encode($r) ?>)'>Edit</button>
                        <form method="POST" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="route_id" value="<?= $r['id'] ?>"><button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this route?">Delete</button></form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Add Route Modal -->
<div class="modal-overlay" id="addRouteModal">
    <div class="modal">
        <div class="modal-header"><h3 class="modal-title" id="routeModalTitle">➕ Add Route</h3><button class="modal-close">✕</button></div>
        <form method="POST" id="routeForm">
            <input type="hidden" name="action" id="routeAction" value="add">
            <input type="hidden" name="route_id" id="editRouteId">
            <div class="form-group"><label class="form-label">Route Name *</label><input type="text" name="route_name" id="routeName" class="form-control" placeholder="e.g. Route 1 - CBD to Githurai" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Origin *</label><input type="text" name="origin" id="routeOrigin" class="form-control" placeholder="e.g. CBD Nairobi" required></div>
                <div class="form-group"><label class="form-label">Destination *</label><input type="text" name="destination" id="routeDestination" class="form-control" placeholder="e.g. Githurai 45" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Distance (km)</label><input type="number" name="distance_km" id="routeDistance" class="form-control" step="0.1" min="0"></div>
                <div class="form-group"><label class="form-label">Fare (KES) *</label><input type="number" name="fare" id="routeFare" class="form-control" step="0.01" min="0" required></div>
            </div>
            <div class="form-group"><label class="form-label">Status</label><select name="status" id="routeStatus" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <button type="submit" class="btn btn-primary btn-block">Save Route</button>
        </form>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function editRoute(r) {
    document.getElementById('routeModalTitle').textContent = '✏️ Edit Route';
    document.getElementById('routeAction').value = 'edit';
    document.getElementById('editRouteId').value = r.id;
    document.getElementById('routeName').value = r.route_name;
    document.getElementById('routeOrigin').value = r.origin;
    document.getElementById('routeDestination').value = r.destination;
    document.getElementById('routeDistance').value = r.distance_km;
    document.getElementById('routeFare').value = r.fare;
    document.getElementById('routeStatus').value = r.status;
    openModal('addRouteModal');
}
</script>
</body>
</html>
