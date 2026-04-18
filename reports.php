<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = sanitize($_POST['title'] ?? '');
    $message  = sanitize($_POST['message'] ?? '');
    $audience = sanitize($_POST['audience'] ?? 'all');

    if (empty($title) || empty($message)) {
        $error = 'Title and message are required.';
    } else {
        $where = 'WHERE status = "active"';
        if ($audience === 'passengers') $where .= ' AND role = "passenger"';
        elseif ($audience === 'drivers') $where .= ' AND role = "driver"';

        $users = $conn->query("SELECT id FROM users $where");
        $count = 0;
        while ($u = $users->fetch_assoc()) {
            createNotification($u['id'], $title, $message);
            $count++;
        }
        $msg = "Notification sent to $count user(s) successfully.";
    }
}

// Recent notifications sent (all users, last 20)
$recent = $conn->query("
    SELECT n.title, n.message, n.created_at, u.full_name, u.role
    FROM notifications n
    JOIN users u ON n.user_id = u.id
    ORDER BY n.created_at DESC
    LIMIT 20
");

$totalNotifs = $conn->query("SELECT COUNT(*) c FROM notifications")->fetch_assoc()['c'];
$unreadCount = $conn->query("SELECT COUNT(*) c FROM notifications WHERE is_read=0")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = 'notifications'; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">🔔 Notifications</h1>
                <p class="page-subtitle">Send broadcast messages to users</p>
            </div>
        </div>

        <div class="stats-grid" style="margin-bottom:20px;">
            <div class="stat-card">
                <div class="stat-icon blue">📨</div>
                <div><div class="stat-value"><?= number_format($totalNotifs) ?></div><div class="stat-label">Total Sent</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">👁️</div>
                <div><div class="stat-value"><?= number_format($unreadCount) ?></div><div class="stat-label">Unread</div></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;">
            <!-- Send Form -->
            <div class="card" style="align-self:start;">
                <div class="card-header"><h3>📤 Send Notification</h3></div>
                <div class="card-body">
                    <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>
                    <form method="POST" class="loading-form">
                        <div class="form-group">
                            <label class="form-label">Audience</label>
                            <select name="audience" class="form-control">
                                <option value="all">Everyone (All Users)</option>
                                <option value="passengers">Passengers Only</option>
                                <option value="drivers">Drivers Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Service Update" required maxlength="150">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Write your notification message..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            📤 Send Notification
                        </button>
                    </form>

                    <!-- Quick templates -->
                    <div style="margin-top:20px;">
                        <p style="font-size:0.82rem;font-weight:700;color:var(--gray);margin-bottom:8px;">Quick Templates:</p>
                        <?php
                        $templates = [
                            ['🚧 Service Disruption', 'There is a temporary service disruption on some routes. We apologise for the inconvenience and are working to restore normal service.'],
                            ['🎉 New Route Available', 'We have added a new route to our network! Check the Trips section to explore new destinations.'],
                            ['💳 Payment Reminder', 'Reminder: Please ensure your pending payments are settled promptly to keep your account in good standing.'],
                            ['⚠️ Schedule Change', 'Please note that trip schedules have been updated. Check the app for the latest departure times.'],
                        ];
                        foreach ($templates as [$t, $m]):
                        ?>
                        <div style="background:#f8f9fa;border-radius:6px;padding:8px 12px;margin-bottom:6px;cursor:pointer;font-size:0.82rem;border:1px solid #eee;"
                             onclick="fillTemplate(this)"
                             data-title="<?= htmlspecialchars($t) ?>"
                             data-message="<?= htmlspecialchars($m) ?>">
                            <?= htmlspecialchars($t) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="card">
                <div class="card-header"><h3>🕐 Recent Notifications</h3></div>
                <div class="card-body" style="padding:0;max-height:520px;overflow-y:auto;">
                    <?php if ($recent->num_rows === 0): ?>
                    <div class="empty-state"><div class="icon">📭</div><p>No notifications sent yet.</p></div>
                    <?php else: while ($n = $recent->fetch_assoc()): ?>
                    <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                            <div style="flex:1;">
                                <div style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars($n['title']) ?></div>
                                <div style="font-size:0.82rem;color:#555;margin:3px 0;"><?= htmlspecialchars($n['message']) ?></div>
                                <div style="font-size:0.75rem;color:var(--gray);">
                                    → <?= htmlspecialchars($n['full_name']) ?>
                                    <span class="badge badge-<?= $n['role']==='passenger'?'success':($n['role']==='driver'?'info':'danger') ?>" style="font-size:0.7rem;"><?= $n['role'] ?></span>
                                </div>
                            </div>
                            <div style="font-size:0.75rem;color:var(--gray);white-space:nowrap;"><?= timeAgo($n['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endwhile; endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
<script>
function fillTemplate(el) {
    document.querySelector('[name="title"]').value = el.dataset.title;
    document.querySelector('[name="message"]').value = el.dataset.message;
    document.querySelector('[name="title"]').focus();
}
</script>
</body>
</html>
