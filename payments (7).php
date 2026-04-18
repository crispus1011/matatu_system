<?php
require_once 'includes/auth.php';
if (isLoggedIn()) { header("Location: index.php"); exit(); }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended. Contact admin.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                // Redirect by role
                if ($user['role'] === 'admin') header("Location: admin/dashboard.php");
                elseif ($user['role'] === 'driver') header("Location: driver/dashboard.php");
                else header("Location: pages/dashboard.php");
                exit();
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EC Matatu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🚌</div>
            <h2>EC Matatu System</h2>
            <p>Sign in to your account</p>
        </div>

        <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
            <div class="alert alert-success">✅ Password reset successfully. Please sign in with your new password.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="loading-form">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter password" required>
                    <span onclick="togglePass('passwordInput')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:1rem;">👁️</span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;padding:12px;">Sign In</button>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:0.88rem;color:var(--gray);">
            Don't have an account? <a href="register.php" style="color:var(--primary);font-weight:600;">Register here</a>
        </p>
        <p style="text-align:center;margin-top:8px;font-size:0.85rem;">
            <a href="reset_password.php" style="color:var(--gray);">Forgot password?</a>
        </p>
        <p style="text-align:center;margin-top:8px;font-size:0.83rem;color:var(--gray);">
            <a href="index.php" style="color:var(--gray);">← Back to Home</a>
        </p>

        <!-- Demo credentials -->
<!--         <div class="alert alert-info" style="margin-top:20px;font-size:0.8rem;">
            <div><strong>Demo Accounts:</strong><br>
            Admin: admin@ecmatatu.co.ke / password<br>
            Driver: james@ecmatatu.co.ke / password<br>
            Passenger: alice@gmail.com / password</div>
        </div> -->
    </div>
</div>
<script>
function togglePass(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
