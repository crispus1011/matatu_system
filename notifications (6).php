<?php
require_once 'includes/auth.php';
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin') header("Location: admin/dashboard.php");
    elseif ($role === 'driver') header("Location: driver/dashboard.php");
    else header("Location: pages/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EC Matatu - Public Transport Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { margin: 0; }
        .landing-nav {
            background: white;
            padding: 0 40px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .landing-nav .brand {
            display: flex; align-items: center; gap: 10px;
            font-size: 1.3rem; font-weight: 800; color: var(--primary);
        }
        .routes-section { background: #f4f6f9; padding: 40px 24px; }
        .routes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; max-width: 1100px; margin: 24px auto 0; }
        .route-card { background: white; border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
        .route-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .cta-section { background: var(--primary); color: white; padding: 60px 24px; text-align: center; }
        section h2 { text-align: center; font-size: 1.6rem; font-weight: 800; color: var(--dark); margin-bottom: 6px; }
        section .subtitle { text-align: center; color: var(--gray); margin-bottom: 32px; }
    </style>
</head>
<body>
<nav class="landing-nav">
    <div class="brand">🚌 EC Matatu</div>
    <div style="display:flex;gap:10px;">
        <a href="login.php" class="btn btn-outline">Login</a>
        <a href="register.php" class="btn btn-primary">Get Started</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <h1>🚌 Book Your Matatu Seat Online</h1>
    <p>Fast, reliable and affordable public transport booking for Nairobi estate residents. Reserve your seat, pay securely and travel with confidence.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
        <a href="register.php" class="btn btn-secondary" style="font-size:1rem;padding:12px 28px;">Start Booking</a>
        <a href="login.php" class="btn btn-outline" style="border-color:white;color:white;font-size:1rem;padding:12px 28px;">Sign In</a>
    </div>
</section>

<!-- FEATURES -->
<section style="padding:50px 24px;background:white;">
    <h2>Why EC Matatu?</h2>
    <p class="subtitle">Everything you need for stress-free daily commuting</p>
    <div class="features-grid">
        <div class="feature-card">
            <div class="icon">📍</div>
            <h3>Route Booking</h3>
            <p>Browse available routes and book your seat in advance — no more crowding at the stage.</p>
        </div>
        <div class="feature-card">
            <div class="icon">💺</div>
            <h3>Seat Selection</h3>
            <p>Choose your preferred seat from an interactive seat map before boarding.</p>
        </div>
        <div class="feature-card">
            <div class="icon">💳</div>
            <h3>M-Pesa & Cash</h3>
            <p>Pay via M-Pesa for cashless convenience, or opt for cash payment on board.</p>
        </div>
        <div class="feature-card">
            <div class="icon">🔔</div>
            <h3>Trip Alerts</h3>
            <p>Get instant notifications about your booking status, departures and updates.</p>
        </div>
        <div class="feature-card">
            <div class="icon">⭐</div>
            <h3>Rate & Review</h3>
            <p>Rate your journey and help us maintain high service quality for all commuters.</p>
        </div>
        <div class="feature-card">
            <div class="icon">📊</div>
            <h3>Admin Control</h3>
            <p>Full management dashboard for routes, drivers, vehicles and financial reporting.</p>
        </div>
    </div>
</section>

<!-- ROUTES PREVIEW -->
<section class="routes-section">
    <h2>Popular Routes</h2>
    <p class="subtitle">Serving major Nairobi corridors daily</p>
    <div class="routes-grid">
        <?php
        require_once 'config/db.php';
        $routes = $conn->query("SELECT * FROM routes WHERE status='active' LIMIT 6");
        while ($r = $routes->fetch_assoc()):
        ?>
        <div class="route-card">
            <div class="route-header">
                <strong><?= htmlspecialchars($r['route_name']) ?></strong>
                <span class="badge badge-success">Active</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;color:#444;margin-bottom:10px;">
                <span>📍 <?= htmlspecialchars($r['origin']) ?></span>
                <span>→</span>
                <span>🏁 <?= htmlspecialchars($r['destination']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:var(--gray);font-size:0.83rem;"><?= $r['distance_km'] ?> km</span>
                <span style="font-size:1.1rem;font-weight:800;color:var(--primary);">KES <?= number_format($r['fare'], 0) ?></span>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <h2 style="color:white;">Ready to Ride?</h2>
    <p style="opacity:0.9;max-width:500px;margin:12px auto 28px;">Join hundreds of estate residents already enjoying hassle-free commuting with EC Matatu.</p>
    <a href="register.php" class="btn btn-secondary" style="font-size:1rem;padding:14px 36px;">Create Free Account</a>
</section>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> EC Matatu System · All rights reserved</p>
</footer>
</body>
</html>
