<?php
/**
 * EC Matatu System — Notifications API
 * GET  /api/notifications.php         → list unread
 * POST /api/notifications.php         → mark all read
 */
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised.']);
    exit();
}
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$userId");
    echo json_encode(['success' => true, 'message' => 'Marked all as read.']);
    exit();
}

$notifs = $conn->query("SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id=$userId ORDER BY created_at DESC LIMIT 10");
$list = [];
while ($n = $notifs->fetch_assoc()) $list[] = $n;
$unread = $conn->query("SELECT COUNT(*) c FROM notifications WHERE user_id=$userId AND is_read=0")->fetch_assoc()['c'];

echo json_encode(['success' => true, 'notifications' => $list, 'unread_count' => (int)$unread]);
