<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$msg = '';
if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    $b = $conn->query("SELECT trip_id FROM bookings WHERE id=$id AND booking_status='confirmed'")->fetch_assoc();
    if ($b) {
        $conn->begin_transaction();
        $conn->query("UPDATE bookings SET booking_status='cancelled' WHERE id=$id");
        $conn->query("UPDATE trips SET available_seats=available_seats+1 WHERE id={$b['trip_id']}");
        $conn->query("UPDATE payments SET payment_status='refunded' WHERE booking_id=$id");
        $conn->commit();
        $msg = 'Booking cancelled.';
    }
}

$statusFilter = sanitize($_GET['status'] ?? 'all');
$where = $statusFilter !== 'all' ? "WHERE b.booking_status='$statusFilter'" : '';

$bookings = $conn->query("
    SELECT b.*, u.full_name, u.phone, r.origin, r.destination, t.departure_time,
           p.payment_method, p.payment_status, p.amount
    FROM bookings b
    JOIN users u ON b.passenger_id=u.id
    JOIN trips t ON b.trip_id=t.id
    JOIN routes r ON t.route_id=r.id
    LEFT JOIN payments p ON p.booking_id=b.id
    $where
    ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "bookings"; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">📋 Bookings Management</h1><p class="page-subtitle">All passenger bookings</p></div>
        </div>

        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

        <div class="tabs" style="margin-bottom:16px;">
            <?php foreach (['all','confirmed','completed','cancelled'] as $s): ?>
            <a href="?status=<?= $s ?>" class="tab-btn <?= $statusFilter===$s?'active':'' ?>" style="border:none;"><?= ucfirst($s) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="search-bar"><input type="text" id="tableSearch" class="form-control" placeholder="🔍 Search bookings..."></div>

        <div class="table-wrapper">
            <table class="searchable-table">
                <thead><tr><th>Ref</th><th>Passenger</th><th>Route</th><th>Departure</th><th>Seat</th><th>Amount</th><th>Payment</th><th>Booking</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($bookings->num_rows === 0): ?>
                <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--gray);">No bookings found.</td></tr>
                <?php else: while ($b = $bookings->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
                    <td><?= htmlspecialchars($b['full_name']) ?><br><small style="color:var(--gray);"><?= htmlspecialchars($b['phone']??'') ?></small></td>
                    <td><?= htmlspecialchars($b['origin']) ?>→<?= htmlspecialchars($b['destination']) ?></td>
                    <td><?= date('d M Y', strtotime($b['departure_time'])) ?><br><small><?= date('H:i', strtotime($b['departure_time'])) ?></small></td>
                    <td>Seat <?= $b['seat_number'] ?></td>
                    <td>KES <?= number_format($b['amount'] ?? 0, 0) ?></td>
                    <td><?= ucfirst($b['payment_method']??'—') ?><br><span class="badge badge-<?= ($b['payment_status']??'pending')==='completed'?'success':'warning' ?>"><?= ucfirst($b['payment_status']??'pending') ?></span></td>
                    <td><span class="badge badge-<?= $b['booking_status']==='confirmed'?'success':($b['booking_status']==='cancelled'?'danger':'info') ?>"><?= ucfirst($b['booking_status']) ?></span></td>
                    <td>
                        <?php if ($b['booking_status'] === 'confirmed'): ?>
                        <a href="?cancel=<?= $b['id'] ?>&status=<?= $statusFilter ?>" class="btn btn-sm btn-danger" data-confirm="Cancel this booking?">Cancel</a>
                        <?php endif; ?>
                    </td>
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
