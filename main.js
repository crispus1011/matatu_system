<?php
/**
 * EC Matatu System — Seat Availability API
 * GET /matatu_system/api/seats.php?trip_id=X
 */
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised.']);
    exit();
}

$tripId = intval($_GET['trip_id'] ?? 0);
if (!$tripId) {
    echo json_encode(['success' => false, 'message' => 'Invalid trip.']);
    exit();
}

$stmt = $conn->prepare("SELECT t.available_seats, t.total_seats FROM trips t WHERE t.id=?");
$stmt->bind_param("i", $tripId);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    echo json_encode(['success' => false, 'message' => 'Trip not found.']);
    exit();
}

$booked = $conn->query("SELECT seat_number FROM bookings WHERE trip_id=$tripId AND booking_status='confirmed'");
$bookedSeats = [];
while ($b = $booked->fetch_assoc()) $bookedSeats[] = (int)$b['seat_number'];

echo json_encode([
    'success'         => true,
    'available_seats' => (int)$trip['available_seats'],
    'total_seats'     => (int)$trip['total_seats'],
    'booked_seats'    => $bookedSeats,
]);
