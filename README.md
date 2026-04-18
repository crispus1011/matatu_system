<?php
require_once '../includes/auth.php';
requireRole('passenger', '../login.php');

$tripId = intval($_GET['trip_id'] ?? 0);
if (!$tripId) { header("Location: trips.php"); exit(); }

// Get trip details
$stmt = $conn->prepare("
    SELECT t.*, r.route_name, r.origin, r.destination, r.fare, r.distance_km,
           v.registration_no, v.model, v.capacity, u.full_name as driver_name
    FROM trips t
    JOIN routes r ON t.route_id = r.id
    JOIN vehicles v ON t.vehicle_id = v.id
    JOIN users u ON t.driver_id = u.id
    WHERE t.id = ? AND t.status = 'scheduled' AND t.departure_time > NOW()
");
$stmt->bind_param("i", $tripId);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) { header("Location: trips.php?error=Trip+not+found"); exit(); }

// Already booked?
$userId = $_SESSION['user_id'];
$alreadyBooked = $conn->prepare("SELECT id FROM bookings WHERE passenger_id=? AND trip_id=? AND booking_status='confirmed'");
$alreadyBooked->bind_param("ii", $userId, $tripId);
$alreadyBooked->execute();
$alreadyBooked = $alreadyBooked->get_result()->num_rows > 0;

// Get booked seats
$bookedSeats = [];
$bsRes = $conn->query("SELECT seat_number FROM bookings WHERE trip_id=$tripId AND booking_status='confirmed'");
while ($bs = $bsRes->fetch_assoc()) $bookedSeats[] = $bs['seat_number'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyBooked) {
    $seatNum = intval($_POST['seat_number'] ?? 0);
    $payMethod = sanitize($_POST['payment_method'] ?? 'cash');
    $mpesaCode = sanitize($_POST['mpesa_code'] ?? '');

    if (!$seatNum || $seatNum < 1 || $seatNum > $trip['capacity']) {
        $error = 'Please select a valid seat.';
    } elseif (in_array($seatNum, $bookedSeats)) {
        $error = 'That seat is already booked. Please choose another.';
    } elseif ($trip['available_seats'] <= 0) {
        $error = 'Sorry, this trip is now fully booked.';
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            $ref = generateBookingRef();
            $stmt = $conn->prepare("INSERT INTO bookings (passenger_id, trip_id, seat_number, booking_ref, booking_status) VALUES (?,?,?,?,'confirmed')");
            $stmt->bind_param("iiis", $userId, $tripId, $seatNum, $ref);
            $stmt->execute();
            $bookingId = $conn->insert_id;

            // Update available seats
            $conn->query("UPDATE trips SET available_seats = available_seats - 1 WHERE id = $tripId");

            // Create payment record
            $payStatus = ($payMethod === 'cash') ? 'pending' : 'completed';
            $stmt2 = $conn->prepare("INSERT INTO payments (booking_id, passenger_id, amount, payment_method, mpesa_code, payment_status) VALUES (?,?,?,?,?,?)");
            $stmt2->bind_param("iidsss", $bookingId, $userId, $trip['fare'], $payMethod, $mpesaCode, $payStatus);
            $stmt2->execute();

            $conn->commit();

            // Notification
            createNotification($userId, 'Booking Confirmed! 🎉', "Your booking $ref for {$trip['origin']} → {$trip['destination']} on " . date('D d M H:i', strtotime($trip['departure_time'])) . " has been confirmed. Seat $seatNum.");

            header("Location: booking_success.php?ref=$ref");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Booking failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Trip - EC Matatu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="layout">
    <?php $currentPage = "book"; include '../includes/passenger_sidebar.php'; ?>

    <main class="main-content">
        <!-- Booking Steps -->
        <div class="booking-steps">
            <div class="step active"><div class="step-num">1</div><span>Select Seat</span></div>
            <div class="step-line"></div>
            <div class="step"><div class="step-num">2</div><span>Payment</span></div>
            <div class="step-line"></div>
            <div class="step"><div class="step-num">3</div><span>Confirm</span></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;">
            <!-- Left: Seat Map + Payment -->
            <div>
                <?php if ($error): ?>
                    <div class="alert alert-error">⚠️ <?= $error ?></div>
                <?php endif; ?>

                <?php if ($alreadyBooked): ?>
                    <div class="alert alert-warning">⚠️ You already have an active booking for this trip. <a href="my_bookings.php">View your bookings →</a></div>
                <?php else: ?>

                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header"><h3>💺 Select Your Seat</h3></div>
                    <div class="card-body">
                        <div class="seat-map-legend">
                            <div class="seat-legend-item"><div class="seat available" style="width:28px;height:28px;font-size:0.7rem;">A</div><span>Available</span></div>
                            <div class="seat-legend-item"><div class="seat booked" style="width:28px;height:28px;font-size:0.7rem;">X</div><span>Booked</span></div>
                            <div class="seat-legend-item"><div class="seat selected" style="width:28px;height:28px;font-size:0.7rem;">✓</div><span>Selected</span></div>
                            <div class="seat-legend-item"><div class="seat driver" style="width:28px;height:28px;font-size:0.7rem;">D</div><span>Driver</span></div>
                        </div>

                        <!-- Matatu layout: driver front + passenger rows -->
                        <div style="background:#f0f4f7;border-radius:12px;padding:16px;max-width:320px;">
                            <!-- Front of vehicle -->
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                                <div style="font-size:0.78rem;color:var(--gray);font-weight:600;">FRONT</div>
                                <div style="font-size:1.5rem;">🚌</div>
                                <div style="font-size:0.78rem;color:var(--gray);font-weight:600;">DOOR</div>
                            </div>
                            <!-- Driver + front seat -->
                            <div style="display:flex;gap:10px;margin-bottom:12px;justify-content:center;">
                                <div class="seat driver">D</div>
                                <div style="width:42px;"></div>
                                <?php
                                $seatNum = 1;
                                $seatClass = in_array($seatNum, $bookedSeats) ? 'booked' : 'available';
                                echo "<div class='seat $seatClass' data-seat='$seatNum'>$seatNum</div>";
                                $seatNum = 2;
                                $seatClass = in_array($seatNum, $bookedSeats) ? 'booked' : 'available';
                                echo "<div class='seat $seatClass' data-seat='$seatNum'>$seatNum</div>";
                                ?>
                            </div>
                            <!-- Remaining rows of 4 -->
                            <div id="seatMap" class="seats-grid">
                                <?php for ($i = 3; $i <= $trip['capacity']; $i++):
                                    $sc = in_array($i, $bookedSeats) ? 'booked' : 'available';
                                ?>
                                <div class="seat <?= $sc ?>" data-seat="<?= $i ?>"><?= $i ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <form method="POST" class="loading-form" id="bookingForm">
                    <input type="hidden" name="seat_number" id="selectedSeat">

                    <div class="card" style="margin-bottom:16px;">
                        <div class="card-header"><h3>💳 Payment Method</h3></div>
                        <div class="card-body">
                            <input type="hidden" name="payment_method" id="paymentMethod" value="cash">
                            <div class="payment-options">
                                <div class="payment-option selected" data-method="cash">
                                    <span class="icon">💵</span> Cash
                                </div>
                                <div class="payment-option" data-method="mpesa">
                                    <span class="icon">📱</span> M-Pesa
                                </div>
                                <div class="payment-option" data-method="card">
                                    <span class="icon">💳</span> Card
                                </div>
                            </div>
                            <div id="mpesaCodeField" style="display:none;">
                                <div class="form-group">
                                    <label class="form-label">M-Pesa Transaction Code</label>
                                    <input type="text" name="mpesa_code" class="form-control" placeholder="e.g. QFX1234567" maxlength="15">
                                    <small style="color:var(--gray);">Send KES <?= number_format($trip['fare'], 0) ?> to 0700 000 001 (EC Matatu) then enter the code.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="bookBtn" disabled style="padding:14px;font-size:1rem;">
                        Select a seat to continue
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Right: Trip Summary -->
            <div>
                <div class="card" style="position:sticky;top:80px;">
                    <div class="card-header"><h3>🗺️ Trip Summary</h3></div>
                    <div class="card-body">
                        <div style="margin-bottom:16px;">
                            <div style="font-size:1.1rem;font-weight:800;margin-bottom:4px;"><?= htmlspecialchars($trip['origin']) ?></div>
                            <div style="color:var(--primary);font-size:1.3rem;margin:6px 0;">↓</div>
                            <div style="font-size:1.1rem;font-weight:800;"><?= htmlspecialchars($trip['destination']) ?></div>
                        </div>

                        <?php
                        $rows = [
                            ['🗺️ Route', $trip['route_name']],
                            ['📏 Distance', $trip['distance_km'] . ' km'],
                            ['🕐 Departure', date('D d M Y', strtotime($trip['departure_time'])) . '<br>' . date('H:i', strtotime($trip['departure_time']))],
                            ['🚌 Vehicle', $trip['registration_no'] . ' (' . $trip['model'] . ')'],
                            ['👤 Driver', $trip['driver_name']],
                            ['💺 Available', $trip['available_seats'] . '/' . $trip['total_seats'] . ' seats'],
                        ];
                        foreach ($rows as [$label, $val]):
                        ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:0.88rem;">
                            <span style="color:var(--gray);"><?= $label ?></span>
                            <span style="font-weight:600;text-align:right;"><?= $val ?></span>
                        </div>
                        <?php endforeach; ?>

                        <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:1.1rem;font-weight:800;color:var(--primary);border-top:2px solid var(--border);margin-top:4px;">
                            <span>Fare</span>
                            <span>KES <?= number_format($trip['fare'], 2) ?></span>
                        </div>

                        <div id="selectedSeatInfo" style="background:#e8f5ee;border-radius:8px;padding:10px;text-align:center;font-size:0.88rem;color:var(--primary);font-weight:600;display:none;">
                            ✅ Seat <span id="seatDisplay"></span> selected
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
<script>
// Start live seat refresh every 15 seconds
startSeatRefresh(<?= $tripId ?>, 15000);

// Override seat selection to show selected seat info
document.querySelectorAll('#seatMap .seat.available, .seat.available[data-seat="1"], .seat.available[data-seat="2"]').forEach(seat => {
    seat.addEventListener('click', () => {
        document.querySelectorAll('.seat.available').forEach(s => s.classList.remove('selected'));
        seat.classList.add('selected');
        document.getElementById('selectedSeat').value = seat.dataset.seat;
        const btn = document.getElementById('bookBtn');
        if (btn) { btn.disabled = false; btn.textContent = '✅ Confirm Booking – Seat ' + seat.dataset.seat; }
        const info = document.getElementById('selectedSeatInfo');
        if (info) { info.style.display = 'block'; document.getElementById('seatDisplay').textContent = seat.dataset.seat; }
    });
});
</script>
</body>
</html>
