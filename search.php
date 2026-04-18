<?php
/**
 * EC Matatu System — Booking API
 * POST /matatu_system/api/book.php
 */
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['user_role'] !== 'passenger') {
    echo json_encode(['success' => false, 'message' => 'Unauthorised.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$tripId    = intval($_POST['trip_id'] ?? 0);
$seatNum   = intval($_POST['seat_number'] ?? 0);
$payMethod = sanitize($_POST['payment_method'] ?? 'cash');
$mpesaCode = sanitize($_POST['mpesa_code'] ?? '');
$userId    = $_SESSION['user_id'];

if (!$tripId || !$seatNum) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

// Validate trip
$stmt = $conn->prepare("SELECT t.*, r.fare FROM trips t JOIN routes r ON t.route_id=r.id WHERE t.id=? AND t.status='scheduled' AND t.departure_time > NOW()");
$stmt->bind_param("i", $tripId);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    echo json_encode(['success' => false, 'message' => 'Trip not available.']);
    exit();
}

// Check already booked
$dup = $conn->prepare("SELECT id FROM bookings WHERE passenger_id=? AND trip_id=? AND booking_status='confirmed'");
$dup->bind_param("ii", $userId, $tripId);
$dup->execute();
if ($dup->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You already have a booking for this trip.']);
    exit();
}

// Check seat taken
$seatCheck = $conn->prepare("SELECT id FROM bookings WHERE trip_id=? AND seat_number=? AND booking_status='confirmed'");
$seatCheck->bind_param("ii", $tripId, $seatNum);
$seatCheck->execute();
if ($seatCheck->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'That seat is already booked. Please select another.']);
    exit();
}

if ($trip['available_seats'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Trip is fully booked.']);
    exit();
}

// Transaction
$conn->begin_transaction();
try {
    $ref = generateBookingRef();
    $stmt = $conn->prepare("INSERT INTO bookings (passenger_id, trip_id, seat_number, booking_ref, booking_status) VALUES (?,?,?,?,'confirmed')");
    $stmt->bind_param("iiis", $userId, $tripId, $seatNum, $ref);
    $stmt->execute();
    $bookingId = $conn->insert_id;

    $conn->query("UPDATE trips SET available_seats = available_seats - 1 WHERE id = $tripId");

    $payStatus = ($payMethod === 'cash') ? 'pending' : 'completed';
    $stmt2 = $conn->prepare("INSERT INTO payments (booking_id, passenger_id, amount, payment_method, mpesa_code, payment_status) VALUES (?,?,?,?,?,?)");
    $stmt2->bind_param("iidsss", $bookingId, $userId, $trip['fare'], $payMethod, $mpesaCode, $payStatus);
    $stmt2->execute();

    $conn->commit();
    createNotification($userId, 'Booking Confirmed! 🎉', "Booking $ref confirmed. Seat $seatNum reserved.");

    echo json_encode([
        'success'     => true,
        'message'     => 'Booking confirmed!',
        'booking_ref' => $ref,
        'redirect'    => '../pages/booking_success.php?ref=' . $ref,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Booking failed. Please try again.']);
}
