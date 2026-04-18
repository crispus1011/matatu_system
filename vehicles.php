<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$msg = ''; $error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $role  = sanitize($_POST['role']);
        $pass  = $_POST['password'];
        // Check email
        if ($conn->query("SELECT id FROM users WHERE email='$email'")->num_rows > 0) {
            $error = 'Email already exists.';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name,email,phone,password,role) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $email, $phone, $hashed, $role);
            $stmt->execute() ? $msg = 'User added successfully.' : ($error = 'Failed to add user.');
        }
    } elseif ($action === 'toggle_status') {
        $uid = intval($_POST['user_id']);
        $status = sanitize($_POST['status']);
        $conn->query("UPDATE users SET status='$status' WHERE id=$uid AND role != 'admin'");
        $msg = 'User status updated.';
    } elseif ($action === 'delete') {
        $uid = intval($_POST['user_id']);
        $conn->query("DELETE FROM users WHERE id=$uid AND role != 'admin'");
        $msg = 'User deleted.';
    }
}

$roleFilter = sanitize($_GET['role'] ?? 'all');
$where = $roleFilter !== 'all' ? "WHERE role='$roleFilter'" : "";
$users = $conn->query("SELECT *, (SELECT COUNT(*) FROM bookings WHERE passenger_id=users.id) as total_bookings FROM users $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "users"; include '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">👥 User Management</h1><p class="page-subtitle">Manage all system users</p></div>
            <button class="btn btn-primary" data-modal="addUserModal">+ Add User</button>
        </div>

        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <!-- Filter tabs -->
        <div class="tabs" style="margin-bottom:16px;">
            <a href="?role=all" class="tab-btn <?= $roleFilter==='all'?'active':'' ?>" style="border:none;">All Users</a>
            <a href="?role=passenger" class="tab-btn <?= $roleFilter==='passenger'?'active':'' ?>" style="border:none;">Passengers</a>
            <a href="?role=driver" class="tab-btn <?= $roleFilter==='driver'?'active':'' ?>" style="border:none;">Drivers</a>
            <a href="?role=admin" class="tab-btn <?= $roleFilter==='admin'?'active':'' ?>" style="border:none;">Admins</a>
        </div>

        <div class="search-bar"><input type="text" id="tableSearch" class="form-control" placeholder="🔍 Search users..."></div>

        <div class="table-wrapper">
            <table class="searchable-table">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Bookings</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><div style="display:flex;align-items:center;gap:8px;"><div class="avatar" style="width:32px;height:32px;font-size:0.8rem;"><?= strtoupper(substr($u['full_name'],0,1)) ?></div><?= htmlspecialchars($u['full_name']) ?></div></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= $u['role']==='admin'?'danger':($u['role']==='driver'?'info':'success') ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td><?= $u['total_bookings'] ?></td>
                    <td><span class="badge badge-<?= $u['status']==='active'?'success':($u['status']==='suspended'?'danger':'secondary') ?>"><?= ucfirst($u['status']) ?></span></td>
                    <td>
                        <a href="user_detail.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline">View</a>
                        <?php if ($u['role'] !== 'admin'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="status" value="<?= $u['status']==='active'?'suspended':'active' ?>">
                            <button type="submit" class="btn btn-sm btn-<?= $u['status']==='active'?'danger':'secondary' ?>"><?= $u['status']==='active'?'Suspend':'Activate' ?></button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this user? This cannot be undone.">Delete</button>
                        </form>
                        <?php else: ?>
                        <span style="color:var(--gray);font-size:0.8rem;">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">➕ Add New User</h3>
            <button class="modal-close">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control"></div>
            </div>
            <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Role *</label><select name="role" class="form-control" required><option value="passenger">Passenger</option><option value="driver">Driver</option><option value="admin">Admin</option></select></div>
                <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" placeholder="Min. 6 chars" required></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create User</button>
        </form>
    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
