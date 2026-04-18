<?php
// ============================================
// EC Matatu System - Auth & Session Helpers
// ============================================
session_start();

require_once __DIR__ . '/../config/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin($redirect = '../index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit();
    }
}

function requireRole($role, $redirect = '../index.php') {
    requireLogin($redirect);
    if ($_SESSION['user_role'] !== $role) {
        header("Location: $redirect");
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    global $conn;
    $id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, role, profile_pic, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function generateBookingRef() {
    return 'EC' . strtoupper(substr(uniqid(), -6)) . rand(10,99);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function formatKES($amount) {
    return 'KES ' . number_format($amount, 2);
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hrs ago';
    return date('d M Y', $time);
}

function getUnreadNotifications($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['cnt'] ?? 0;
}

function createNotification($userId, $title, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?,?,?)");
    $stmt->bind_param("iss", $userId, $title, $message);
    $stmt->execute();
}
