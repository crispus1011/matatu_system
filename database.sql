<?php
/**
 * EC Matatu System — Stats API
 * GET /api/stats.php  (admin only)
 */
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorised.']);
    exit();
}

echo json_encode([
    'success'         => true,
    'total_users'     => (int)$conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'],
    'total_bookings'  => (int)$conn->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'],
    'total_revenue'   => (float)$conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE payment_status='completed'")->fetch_assoc()['s'],
    'active_trips'    => (int)$conn->query("SELECT COUNT(*) c FROM trips WHERE status='ongoing'")->fetch_assoc()['c'],
    'pending_payments'=> (int)$conn->query("SELECT COUNT(*) c FROM payments WHERE payment_status='pending'")->fetch_assoc()['c'],
    'timestamp'       => date('Y-m-d H:i:s'),
]);
