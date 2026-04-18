<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $reg      = sanitize($_POST['registration_no']);
        $model    = sanitize($_POST['model']);
        $capacity = intval($_POST['capacity']);
        $driverId = intval($_POST['driver_id']) ?: null;
        $status   = sanitize($_POST['status']);

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO vehicles (registration_no,model,capacity,driver_id,status) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssiss", $reg, $model, $capacity, $driverId, $status);
            $stmt->execute() ? $msg = 'Vehicle added.' : ($error = 'Failed. Reg no may already exist.');
        } else {
            $id = intval($_POST['vehicle_id']);
            $stmt = $conn->prepare("UPDATE vehicles SET registration_no=?,model=?,capacity=?,driver_id=?,status=? WHERE id=?");
            $stmt->bind_param("ssiiss", $reg, $model, $capacity, $driverId, $status, $id);
            $stmt->execute() ? $msg = 'Vehicle updated.' : ($error = 'Update failed.');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['vehicle_id']);
        $conn->query("DELETE FROM vehicles WHERE id=$id") ? $msg = 'Vehicle deleted.' : ($error = 'Cannot delete.');
    }
}

$vehicles = $conn->query("
    SELECT v.*, u.full_name as driver_name
    FROM vehicles v LEFT JOIN users u ON v.driver_id=u.id
    ORDER BY v.registration_no
");
$drivers = $conn->query("SELECT id, full_name FROM users WHERE role='driver' AND status='active' ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "vehicles"; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">🚌 Vehicle Management</h1><p class="page-subtitle">Manage the matatu fleet</p></div>
            <button class="btn btn-primary" data-modal="vehicleModal">+ Add Vehicle</button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Registration</th><th>Model</th><th>Capacity</th><th>Assigned Driver</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; while ($v = $vehicles->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($v['registration_no']) ?></strong></td>
                    <td><?= htmlspecialchars($v['model'] ?? '—') ?></td>
                    <td><?= $v['capacity'] ?> seats</td>
                    <td><?= htmlspecialchars($v['driver_name'] ?? 'Unassigned') ?></td>
                    <td><span class="badge badge-<?= $v['status']==='active'?'success':($v['status']==='maintenance'?'warning':'secondary') ?>"><?= ucfirst($v['status']) ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick='editVehicle(<?= json_encode($v) ?>)'>Edit</button>
                        <form method="POST" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>"><button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this vehicle?">Delete</button></form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Vehicle Modal -->
<div class="modal-overlay" id="vehicleModal">
    <div class="modal">
        <div class="modal-header"><h3 class="modal-title" id="vModalTitle">🚌 Add Vehicle</h3><button class="modal-close">✕</button></div>
        <form method="POST">
            <input type="hidden" name="action" id="vAction" value="add">
            <input type="hidden" name="vehicle_id" id="vId">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Registration No *</label><input type="text" name="registration_no" id="vReg" class="form-control" placeholder="KCA 001A" required></div>
                <div class="form-group"><label class="form-label">Model</label><input type="text" name="model" id="vModel" class="form-control" placeholder="Toyota Hiace 2020"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Capacity (seats)</label><input type="number" name="capacity" id="vCapacity" class="form-control" value="14" min="1" max="60"></div>
                <div class="form-group"><label class="form-label">Status</label><select name="status" id="vStatus" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="maintenance">Maintenance</option></select></div>
            </div>
            <div class="form-group">
                <label class="form-label">Assign Driver</label>
                <select name="driver_id" id="vDriver" class="form-control">
                    <option value="">— Unassigned —</option>
                    <?php $drivers->data_seek(0); while ($d = $drivers->fetch_assoc()): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Save Vehicle</button>
        </form>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function editVehicle(v) {
    document.getElementById('vModalTitle').textContent = '✏️ Edit Vehicle';
    document.getElementById('vAction').value = 'edit';
    document.getElementById('vId').value = v.id;
    document.getElementById('vReg').value = v.registration_no;
    document.getElementById('vModel').value = v.model || '';
    document.getElementById('vCapacity').value = v.capacity;
    document.getElementById('vStatus').value = v.status;
    document.getElementById('vDriver').value = v.driver_id || '';
    openModal('vehicleModal');
}
</script>
</body>
</html>
