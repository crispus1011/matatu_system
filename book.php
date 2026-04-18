<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$userId = intval($_GET['id'] ?? 0);
if (!$userId) { header("Location: users.php"); exit(); }

$user = $conn->query("SELECT * FROM users WHERE id=$userId")->fetch_assoc();
if (!$user) { header("Location: users.php?error=User+not+found"); exit(); }

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = sanitize($_POST['full_name']);
    $phone  = sanitize($_POST['phone']);
    $role   = sanitize($_POST['role']);
    $status = sanitize($_POST['status']);
    $newPw  = $_POST['new_password'] ?? '';

    $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, role=?, status=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $phone, $role, $status, $userId);
    $stmt->execute();

    if (!empty($newPw)) {
        if (strlen($newPw) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hashed = password_hash($newPw, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hashed' WHERE id=$userId");
        }
    }

    if (!$error) {
        $msg = 'User updated successfully.';
        $user = $conn->query("SELECT * FROM users WHERE id=$userId")->fetch_assoc();
    }
}

// User stats
$totalBookings  = $conn->query("SELECT COUNT(*) c FROM bookings WHERE passenger_id=$userId")->fetch_assoc()['c'];
$totalPayments  = $conn->query("SELECT IFNULL(SUM(amount),0) s FROM payments WHERE passenger_id=$userId AND payment_status='completed'")->fetch_assoc()['s'];
$totalTrips     = $conn->query("SELECT COUNT(*) c FROM trips WHERE driver_id=$userId")->fetch_assoc()['c'];

// User activity
$recentBookings = $conn->query("
    SELECT b.booking_ref, b.booking_status, b.seat_number, r.origin, r.destination, t.departure_time
    FROM bookings b
    JOIN trips t ON b.trip_id=t.id
    JOIN routes r ON t.route_id=r.id
    WHERE b.passenger_id=$userId
    ORDER BY b.created_at DESC LIMIT 5
");
$recentTrips = $conn->query("
    SELECT t.departure_time, t.status, r.origin, r.destination,
           (SELECT COUNT(*) FROM bookings b WHERE b.trip_id=t.id) as passengers
    FROM trips t JOIN routes r ON t.route_id=r.id
    WHERE t.driver_id=$userId
    ORDER BY t.departure_time DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Detail - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = 'users'; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">👤 User Detail</h1>
                <p class="page-subtitle"><?= htmlspecialchars($user['full_name']) ?> · <?= ucfirst($user['role']) ?></p>
            </div>
            <a href="users.php" class="btn btn-outline">← Back to Users</a>
        </div>

        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:20px;">
            <!-- Edit Form -->
            <div>
                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header"><h3>✏️ Edit User</h3></div>
                    <div class="card-body">
                        <div style="text-align:center;margin-bottom:20px;">
                            <div class="avatar" style="width:72px;height:72px;font-size:2rem;margin:0 auto 10px;">
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            </div>
                            <span class="badge badge-<?= $user['role']==='admin'?'danger':($user['role']==='driver'?'info':'success') ?>"><?= ucfirst($user['role']) ?></span>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-control">
                                        <option value="passenger" <?= $user['role']==='passenger'?'selected':'' ?>>Passenger</option>
                                        <option value="driver"    <?= $user['role']==='driver'?'selected':'' ?>>Driver</option>
                                        <option value="admin"     <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active"    <?= $user['status']==='active'?'selected':'' ?>>Active</option>
                                        <option value="inactive"  <?= $user['status']==='inactive'?'selected':'' ?>>Inactive</option>
                                        <option value="suspended" <?= $user['status']==='suspended'?'selected':'' ?>>Suspended</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">New Password <small style="color:var(--gray);">(leave blank to keep)</small></label>
                                <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Account info -->
                <div class="card">
                    <div class="card-header"><h3>ℹ️ Account Info</h3></div>
                    <div class="card-body">
                        <?php
                        $rows = [
                            ['User ID', '#' . $user['id']],
                            ['Registered', date('d M Y H:i', strtotime($user['created_at']))],
                            ['Total Bookings', $totalBookings],
                            ['Total Paid', 'KES ' . number_format($totalPayments, 0)],
                        ];
                        if ($user['role'] === 'driver') $rows[] = ['Trips Driven', $totalTrips];
                        foreach ($rows as [$label, $val]):
                        ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:0.88rem;">
                            <span style="color:var(--gray);"><?= $label ?></span>
                            <strong><?= $val ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Activity -->
            <div>
                <?php if ($user['role'] === 'passenger' && $recentBookings->num_rows > 0): ?>
                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header"><h3>📋 Recent Bookings</h3></div>
                    <div class="card-body" style="padding:0;">
                        <?php while ($b = $recentBookings->fetch_assoc()): ?>
                        <div style="padding:10px 16px;border-bottom:1px solid #f0f0f0;font-size:0.85rem;">
                            <div style="font-weight:700;"><?= htmlspecialchars($b['origin']) ?> → <?= htmlspecialchars($b['destination']) ?></div>
                            <div style="color:var(--gray);margin-top:2px;">
                                <?= date('d M Y H:i', strtotime($b['departure_time'])) ?>
                                · Seat <?= $b['seat_number'] ?>
                                · <strong><?= htmlspecialchars($b['booking_ref']) ?></strong>
                                · <span class="badge badge-<?= $b['booking_status']==='confirmed'?'success':($b['booking_status']==='cancelled'?'danger':'info') ?>"><?= ucfirst($b['booking_status']) ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($user['role'] === 'driver' && $recentTrips->num_rows > 0): ?>
                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header"><h3>🚌 Recent Trips</h3></div>
                    <div class="card-body" style="padding:0;">
                        <?php while ($t = $recentTrips->fetch_assoc()): ?>
                        <div style="padding:10px 16px;border-bottom:1px solid #f0f0f0;font-size:0.85rem;">
                            <div style="font-weight:700;"><?= htmlspecialchars($t['origin']) ?> → <?= htmlspecialchars($t['destination']) ?></div>
                            <div style="color:var(--gray);margin-top:2px;">
                                <?= date('d M Y H:i', strtotime($t['departure_time'])) ?>
                                · <?= $t['passengers'] ?> passengers
                                · <span class="badge badge-<?= $t['status']==='completed'?'success':($t['status']==='ongoing'?'warning':'info') ?>"><?= ucfirst($t['status']) ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Send notification to this user -->
                <div class="card">
                    <div class="card-header"><h3>🔔 Send Notification</h3></div>
                    <div class="card-body">
                        <form method="POST" action="notifications.php" id="notifForm">
                            <input type="hidden" name="audience" value="single">
                            <input type="hidden" name="user_id" value="<?= $userId ?>">
                            <div class="form-group">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" placeholder="Notification title" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="3" placeholder="Your message..." required></textarea>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="sendDirectNotif()">Send Now</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
<script>
async function sendDirectNotif() {
    const form = document.getElementById('notifForm');
    const title   = form.querySelector('[name="title"]').value.trim();
    const message = form.querySelector('[name="message"]').value.trim();
    if (!title || !message) { showToast('Please fill in title and message.', 'error'); return; }

    const fd = new FormData();
    fd.append('title', title);
    fd.append('message', message);
    fd.append('user_id', '<?= $userId ?>');

    try {
        const res = await fetch('../api/notify_user.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('Notification sent!', 'success');
            form.reset();
        } else {
            showToast(data.message || 'Failed to send.', 'error');
        }
    } catch(e) {
        showToast('Network error.', 'error');
    }
}
</script>
</body>
</html>
