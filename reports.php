<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Reports';
$activeNav = 'reports';

// Revenue for the last 7 days (fill in missing days with 0)
$raw = $pdo->query(
    "SELECT DATE(exit_time) AS d, SUM(fee) AS revenue, COUNT(*) AS trips
     FROM vehicles
     WHERE status = 'exited' AND exit_time >= CURDATE() - INTERVAL 6 DAY
     GROUP BY DATE(exit_time)"
)->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

$days = [];
$revenueSeries = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} day"));
    $label = date('D', strtotime($date));
    $days[] = $label;
    $revenueSeries[] = isset($raw[$date]) ? (float) $raw[$date]['revenue'] : 0.0;
}

$totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(fee), 0) FROM vehicles WHERE status = 'exited'")->fetchColumn();
$totalTrips   = (int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'exited'")->fetchColumn();

$typeSplit = $pdo->query(
    "SELECT vehicle_type, COUNT(*) AS c FROM vehicles GROUP BY vehicle_type"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$carCount  = $typeSplit['car'] ?? 0;
$bikeCount = $typeSplit['bike'] ?? 0;

$maxRevenue = max(1, ...$revenueSeries);

require __DIR__ . '/includes/header.php';
?>

<div class="grid grid-3">
    <div class="card">
        <div class="card-title">All-time revenue</div>
        <div class="card-value meter">₹<?= number_format($totalRevenue, 2) ?></div>
    </div>
    <div class="card">
        <div class="card-title">Completed trips</div>
        <div class="card-value meter"><?= $totalTrips ?></div>
    </div>
    <div class="card">
        <div class="card-title">Vehicle split (all-time)</div>
        <div class="card-value meter"><?= $carCount ?> cars · <?= $bikeCount ?> bikes</div>
    </div>
</div>

<div class="section-title">Revenue — last 7 days</div>
<div class="card">
    <div style="display:flex; align-items:flex-end; gap:16px; height:180px; padding-top:10px;">
        <?php foreach ($revenueSeries as $i => $rev): ?>
            <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%;">
                <div class="meter" style="font-size:12px; margin-bottom:6px; color:var(--ink-soft);">
                    <?= $rev > 0 ? '₹' . number_format($rev, 0) : '—' ?>
                </div>
                <div style="width:100%; max-width:36px; background:var(--safety-yellow);
                            height:<?= max(4, ($rev / $maxRevenue) * 130) ?>px; border-radius:2px 2px 0 0;"></div>
                <div style="font-size:12px; color:var(--ink-soft); margin-top:8px;"><?= $days[$i] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
