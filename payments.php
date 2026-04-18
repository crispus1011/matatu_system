<?php
require_once '../includes/auth.php';
requireRole('admin', '../login.php');

$avgRating    = $conn->query("SELECT ROUND(AVG(rating),2) r FROM feedback")->fetch_assoc()['r'] ?? 0;
$totalReviews = $conn->query("SELECT COUNT(*) c FROM feedback")->fetch_assoc()['c'];
$fiveStar     = $conn->query("SELECT COUNT(*) c FROM feedback WHERE rating=5")->fetch_assoc()['c'];
$oneStar      = $conn->query("SELECT COUNT(*) c FROM feedback WHERE rating=1")->fetch_assoc()['c'];

// Rating distribution
$dist = [];
for ($i = 1; $i <= 5; $i++) {
    $dist[$i] = $conn->query("SELECT COUNT(*) c FROM feedback WHERE rating=$i")->fetch_assoc()['c'];
}

$ratingFilter = intval($_GET['rating'] ?? 0);
$where = $ratingFilter ? "WHERE f.rating=$ratingFilter" : '';

$feedback = $conn->query("
    SELECT f.*, u.full_name, u.email, r.origin, r.destination
    FROM feedback f
    JOIN users u ON f.passenger_id = u.id
    JOIN trips t ON f.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    $where
    ORDER BY f.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        .star-filled { color: #f5a623; }
        .star-empty  { color: #ddd; }
        .rating-bar-row { display:flex; align-items:center; gap:10px; margin-bottom:8px; font-size:0.85rem; }
        .rating-bar-track { flex:1; background:#eee; border-radius:4px; height:10px; overflow:hidden; }
        .rating-bar-fill  { background:#f5a623; height:100%; border-radius:4px; transition:width 0.5s; }
        .big-rating { font-size:3.5rem; font-weight:900; color:var(--primary); line-height:1; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="layout">
    <?php $currentPage = "feedback"; include '../includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div><h1 class="page-title">⭐ Customer Feedback</h1><p class="page-subtitle">Trip ratings and passenger reviews</p></div>
        </div>

        <!-- Summary -->
        <div style="display:grid;grid-template-columns:220px 1fr;gap:20px;margin-bottom:24px;">
            <div class="card">
                <div class="card-body" style="text-align:center;padding:28px 20px;">
                    <div class="big-rating"><?= number_format($avgRating, 1) ?></div>
                    <div style="margin:8px 0;">
                        <?php for ($i=1; $i<=5; $i++): ?>
                        <span class="<?= $i <= round($avgRating) ? 'star-filled' : 'star-empty' ?>" style="font-size:1.3rem;">★</span>
                        <?php endfor; ?>
                    </div>
                    <div style="color:var(--gray);font-size:0.88rem;"><?= $totalReviews ?> total reviews</div>
                    <div style="margin-top:12px;display:flex;justify-content:center;gap:16px;font-size:0.8rem;">
                        <div style="color:#28a745;">⬆ <?= $fiveStar ?> × 5★</div>
                        <div style="color:#dc3545;">⬇ <?= $oneStar ?> × 1★</div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3>Rating Distribution</h3></div>
                <div class="card-body">
                    <?php for ($i=5; $i>=1; $i--): $pct = $totalReviews > 0 ? ($dist[$i]/$totalReviews)*100 : 0; ?>
                    <div class="rating-bar-row">
                        <a href="?rating=<?= $i ?>" style="min-width:40px;color:var(--gray);<?= $ratingFilter===$i?'font-weight:800;color:var(--primary)':'' ?>"><?= $i ?>★</a>
                        <div class="rating-bar-track"><div class="rating-bar-fill" style="width:<?= $pct ?>%"></div></div>
                        <span style="min-width:36px;color:var(--gray);"><?= $dist[$i] ?></span>
                    </div>
                    <?php endfor; ?>
                    <?php if ($ratingFilter): ?>
                    <a href="feedback.php" class="btn btn-sm btn-outline" style="margin-top:8px;">✕ Clear filter</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Review Cards -->
        <?php if ($feedback->num_rows === 0): ?>
        <div class="card"><div class="card-body"><div class="empty-state"><div class="icon">💬</div><h3>No feedback yet</h3></div></div></div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
        <?php while ($f = $feedback->fetch_assoc()):
            $stars = str_repeat('★', $f['rating']) . str_repeat('☆', 5 - $f['rating']);
            $starColors = ['','#dc3545','#fd7e14','#ffc107','#28a745','#20c997'];
        ?>
        <div class="card" style="border-top:3px solid <?= $starColors[$f['rating']] ?>;">
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                    <div>
                        <div style="font-weight:700;"><?= htmlspecialchars($f['full_name']) ?></div>
                        <div style="font-size:0.8rem;color:var(--gray);"><?= htmlspecialchars($f['email']) ?></div>
                    </div>
                    <div style="font-size:1.2rem;color:<?= $starColors[$f['rating']] ?>;font-weight:800;"><?= $stars ?></div>
                </div>
                <div style="font-size:0.82rem;color:var(--gray);margin-bottom:8px;">
                    🗺️ <?= htmlspecialchars($f['origin']) ?> → <?= htmlspecialchars($f['destination']) ?>
                </div>
                <?php if ($f['comment']): ?>
                <div style="background:#f8f9fa;border-radius:8px;padding:10px;font-size:0.88rem;color:#444;font-style:italic;">
                    "<?= htmlspecialchars($f['comment']) ?>"
                </div>
                <?php endif; ?>
                <div style="font-size:0.78rem;color:var(--gray);margin-top:8px;"><?= timeAgo($f['created_at']) ?></div>
            </div>
        </div>
        <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
