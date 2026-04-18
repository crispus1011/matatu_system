<?php
require_once '../includes/auth.php';
requireRole('driver', '../login.php');
$userId = $_SESSION['user_id'];
$tripId = intval($_GET['id'] ?? 0);
$status = sanitize($_GET['status'] ?? '');

$allowed = ['ongoing', 'completed', 'cancelled'];
if (!$tripId || !in_array($status, $allowed)) { header("Location: my_trips.php"); exit(); }

$stmt = $conn->prepare("UPDATE trips SET status=? WHERE id=? AND driver_id=?");
$stmt->bind_param("sii", $status, $tripId, $userId);
$stmt->execute();

// If completed, mark all confirmed bookings as completed
if ($status === 'completed') {
    $conn->query("UPDATE bookings SET booking_status='completed' WHERE trip_id=$tripId AND booking_status='confirmed'");
    $conn->query("UPDATE payments SET payment_status='completed' WHERE booking_id IN (SELECT id FROM bookings WHERE trip_id=$tripId)");
    // Notify passengers
    $passengers = $conn->query("SELECT passenger_id FROM bookings WHERE trip_id=$tripId");
    while ($p = $passengers->fetch_assoc()) {
        createNotification($p['passenger_id'], 'Trip Completed ✅', 'Your trip has been completed. Thank you for riding with EC Matatu!');
    }
}

header("Location: my_trips.php?msg=updated");
exit();
