<?php
/**
 * EC Matatu — Notify Single User API
 * POST /api/notify_user.php
 */
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorised.']);
    exit();
}

$userId  = intval($_POST['user_id'] ?? 0);
$title   = sanitize($_POST['title'] ?? '');
$message = sanitize($_POST['message'] ?? '');

if (!$userId || empty($title) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Missing fields.']);
    exit();
}

$check = $conn->query("SELECT id FROM users WHERE id=$userId");
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

createNotification($userId, $title, $message);
echo json_encode(['success' => true, 'message' => 'Notification sent.']);
