<?php
require_once __DIR__ . '/includes/auth.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - EC Matatu</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .error-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #f0f4f8 0%, #e8f5ee 100%);
        }
        .error-code {
            font-size: 8rem;
            font-weight: 900;
            color: var(--primary);
            line-height: 1;
            opacity: 0.15;
            position: absolute;
        }
        .error-content { position: relative; z-index: 1; }
        .error-icon { font-size: 5rem; margin-bottom: 16px; animation: bounce 1.5s infinite; }
        @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
    </style>
</head>
<body>
<?php if (isLoggedIn()) include 'includes/navbar.php'; ?>
<div class="error-wrap">
    <div>
        <div class="error-code">404</div>
        <div class="error-content">
            <div class="error-icon">🚌</div>
            <h1 style="font-size:1.8rem;font-weight:800;color:var(--dark);margin-bottom:10px;">Page Not Found</h1>
            <p style="color:var(--gray);max-width:380px;margin:0 auto 28px;">
                Looks like this matatu took a wrong turn! The page you're looking for doesn't exist.
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <?php if (isLoggedIn()): ?>
                    <?php
                    $role = $_SESSION['user_role'] ?? 'passenger';
                    $home = $role === 'admin' ? 'admin/dashboard.php' : ($role === 'driver' ? 'driver/dashboard.php' : 'pages/dashboard.php');
                    ?>
                    <a href="<?= BASE_URL ?>/<?= $home ?>" class="btn btn-primary">🏠 Go to Dashboard</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/" class="btn btn-primary">🏠 Go Home</a>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">🔑 Login</a>
                <?php endif; ?>
                <a href="javascript:history.back()" class="btn btn-outline">← Go Back</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
