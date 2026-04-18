<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$tripId = intval($_GET['id'] ?? 0);
if (!$tripId) { header("Location: trips.php"); exit(); }

$trip = $conn->query("
    SELECT t.*, r.route_name, r.origin, r.destination, r.fare, r.distance_km,
           v.registration_no, v.model, v.capacity,
           u.full_name as driver_name, u.phone as driver_phone
    FROM trips t
    JOIN routes r ON t.route_id=r.id
    JOIN vehicles v ON t.vehicle_id=v.id
    JOIN users u ON t.driver_id=u.id
    WHERE t.id=$tripId
")->fetch_assoc();
if (!$trip) { header("Location: trips.php"); exit(); }

$passengers = $conn->query("
    SELECT b.seat_number, b.booking_ref, b.booking_status, b.created_at,
           u.full_name, u.email, u.phone,
           p.payment_method, p.payment_status, p.amount
    FROM bookings b
    JOIN users u ON b.passenger_id=u.id
    LEFT JOIN payments p ON p.booking_id=b.id
    WHERE b.trip_id=$tripId
    ORDER BY b.seat_number ASC
");

$totalRevenue = $conn->query("
    SELECT IFNULL(SUM(p.amount),0) s
    FROM payments p JOIN bookings b ON p.booking_id=b.id
    WHERE b.trip_id=$tripId AND p.payment_status='completed'
")->fetch_assoc()['s'];

$bookedCount = $conn->query("SELECT COUNT(*) c FROM bookings WHERE trip_id=$tripId AND booking_status='confirmed'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Detail - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>@media print { .no-print{display:none!important;} }</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = 'trips'; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header no-print">
            <div>
                <h1 class="page-title">📋 Trip Detail</h1>
                <p class="page-subtitle"><?= htmlspecialchars($trip['origin']) ?> → <?= htmlspecialchars($trip['destination']) ?></p>
            </div>
            <div style="display:flex;gap:10px;">
                <button onclick="window.print()" class="btn btn-outline">🖨️ Print Manifest</button>
                <a href="trips.php" class="btn btn-primary">← Back</a>
            </div>
        </div>

        <!-- Trip summary cards -->
        <div class="stats-grid" style="margin-bottom:20px;">
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div><div class="stat-value"><?= $bookedCount ?>/<?= $trip['total_seats'] ?></div><div class="stat-label">Seats Booked</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">💰</div>
                <div><div class="stat-value">KES <?= number_format($totalRevenue, 0) ?></div><div class="stat-label">Revenue Collected</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">💺</div>
                <div><div class="stat-value"><?= $trip['available_seats'] ?></div><div class="stat-label">Seats Remaining</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon <?= $trip['status']==='completed'?'blue':($trip['status']==='ongoing'?'orange':'green') ?>">🚌</div>
                <div><div class="stat-value"><?= ucfirst($trip['status']) ?></div><div class="stat-label">Trip Status</div></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1.8fr;gap:20px;margin-bottom:20px;">
            <!-- Trip Info -->
            <div class="card" style="align-self:start;">
                <div class="card-header"><h3>🗺️ Trip Information</h3></div>
                <div class="card-body">
                    <?php
                    $info = [
                        ['Route',     $trip['route_name']],
                        ['From',      $trip['origin']],
                        ['To',        $trip['destination']],
                        ['Distance',  $trip['distance_km'] . ' km'],
                        ['Fare',      'KES ' . number_format($trip['fare'], 2)],
                        ['Departure', date('D d M Y', strtotime($trip['departure_time'])) . ' at ' . date('H:i', strtotime($trip['departure_time']))],
                        ['Vehicle',   $trip['registration_no'] . ' (' . ($trip['model'] ?? 'N/A') . ')'],
                        ['Capacity',  $trip['capacity'] . ' seats'],
                        ['Driver',    $trip['driver_name']],
                        ['Driver Tel',$trip['driver_phone'] ?? '—'],
                    ];
                    foreach ($info as [$label, $val]):
                    ?>
                    <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #f0f0f0;font-size:0.87rem;">
                        <span style="color:var(--gray);"><?= $label ?></span>
                        <span style="font-weight:600;text-align:right;max-width:55%;"><?= htmlspecialchars($val) ?></span>
                    </div>
                    <?php endforeach; ?>

                    <!-- Admin status override -->
                    <div class="no-print" style="margin-top:16px;">
                        <form method="POST" action="trips.php">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="trip_id" value="<?= $tripId ?>">
                            <input type="hidden" name="route_id" value="<?= $trip['route_id'] ?>">
                            <input type="hidden" name="vehicle_id" value="<?= $trip['vehicle_id'] ?>">
                            <input type="hidden" name="driver_id" value="<?= $trip['driver_id'] ?>">
                            <input type="hidden" name="departure_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['departure_time'])) ?>">
                            <input type="hidden" name="total_seats" value="<?= $trip['total_seats'] ?>">
                            <div class="form-group" style="margin-bottom:8px;">
                                <label class="form-label">Update Status</label>
                                <select name="status" class="form-control" style="margin-bottom:6px;">
                                    <?php foreach (['scheduled','ongoing','completed','cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $trip['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block btn-sm">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Seat Visual -->
            <div class="card">
                <div class="card-header"><h3>💺 Seat Map</h3></div>
                <div class="card-body">
                    <?php
                    $bookedSeatsArr = [];
                    $passengersBySeat = [];
                    // re-query for seat map
                    $sm = $conn->query("
                        SELECT b.seat_number, b.booking_status, u.full_name
                        FROM bookings b JOIN users u ON b.passenger_id=u.id
                        WHERE b.trip_id=$tripId
                    ");
                    while ($s = $sm->fetch_assoc()) {
                        $bookedSeatsArr[] = $s['seat_number'];
                        $passengersBySeat[$s['seat_number']] = $s;
                    }
                    ?>
                    <div class="seat-map-legend" style="margin-bottom:12px;">
                        <div class="seat-legend-item"><div class="seat available" style="width:28px;height:28px;font-size:0.7rem;">A</div> Available</div>
                        <div class="seat-legend-item"><div class="seat booked" style="width:28px;height:28px;font-size:0.7rem;">X</div> Booked</div>
                        <div class="seat-legend-item"><div class="seat driver" style="width:28px;height:28px;font-size:0.7rem;">D</div> Driver</div>
                    </div>
                    <div style="background:#f0f4f7;border-radius:12px;padding:16px;max-width:340px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:0.78rem;color:var(--gray);font-weight:600;">
                            <span>FRONT</span><span style="font-size:1.3rem;">🚌</span><span>DOOR</span>
                        </div>
                        <!-- Driver row -->
                        <div style="display:flex;gap:10px;margin-bottom:10px;justify-content:center;">
                            <div class="seat driver" title="Driver">D</div>
                            <div style="width:42px;"></div>
                            <?php for ($s=1; $s<=2; $s++):
                                $isBkd = in_array($s, $bookedSeatsArr);
                                $title = $isBkd ? htmlspecialchars($passengersBySeat[$s]['full_name'] ?? '') : 'Available';
                            ?>
                            <div class="seat <?= $isBkd ? 'booked' : 'available' ?>" title="Seat <?= $s ?>: <?= $title ?>"><?= $s ?></div>
                            <?php endfor; ?>
                        </div>
                        <!-- Passenger rows -->
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                        <?php for ($s=3; $s<=$trip['capacity']; $s++):
                            $isBkd = in_array($s, $bookedSeatsArr);
                            $pName = $isBkd ? htmlspecialchars($passengersBySeat[$s]['full_name'] ?? '') : 'Available';
                        ?>
                        <div class="seat <?= $isBkd ? 'booked' : 'available' ?>" title="Seat <?= $s ?>: <?= $pName ?>"><?= $s ?></div>
                        <?php endfor; ?>
                        </div>
                    </div>
                    <p style="font-size:0.78rem;color:var(--gray);margin-top:10px;">💡 Hover over a seat to see passenger name.</p>
                </div>
            </div>
        </div>

        <!-- Passenger manifest -->
        <div class="card">
            <div class="card-header">
                <h3>👥 Passenger Manifest (<?= $passengers->num_rows ?>)</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Seat</th><th>Passenger</th><th>Phone</th><th>Booking Ref</th><th>Payment Method</th><th>Amount</th><th>Pay Status</th><th>Booking Status</th></tr></thead>
                    <tbody>
                    <?php if ($passengers->num_rows === 0): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray);">No passengers booked for this trip.</td></tr>
                    <?php else: while ($p = $passengers->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= $p['seat_number'] ?></strong></td>
                        <td>
                            <?= htmlspecialchars($p['full_name']) ?><br>
                            <small style="color:var(--gray);"><?= htmlspecialchars($p['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                        <td><strong><?= htmlspecialchars($p['booking_ref']) ?></strong></td>
                        <td><?= ucfirst($p['payment_method'] ?? '—') ?></td>
                        <td>KES <?= number_format($p['amount'] ?? 0, 0) ?></td>
                        <td><span class="badge badge-<?= ($p['payment_status']??'pending')==='completed'?'success':($p['payment_status']==='refunded'?'info':'warning') ?>"><?= ucfirst($p['payment_status'] ?? 'pending') ?></span></td>
                        <td><span class="badge badge-<?= $p['booking_status']==='confirmed'?'success':($p['booking_status']==='cancelled'?'danger':'info') ?>"><?= ucfirst($p['booking_status']) ?></span></td>
                    </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
