<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

// Overall slot counts
$totalSlots    = (int) $pdo->query("SELECT COUNT(*) FROM slots")->fetchColumn();
$occupiedSlots = (int) $pdo->query("SELECT COUNT(*) FROM slots WHERE status = 'occupied'")->fetchColumn();
$availableSlots = $totalSlots - $occupiedSlots;

// Per-type breakdown
$carTotal = (int) $pdo->query("SELECT COUNT(*) FROM slots WHERE vehicle_type = 'car'")->fetchColumn();
$carOcc   = (int) $pdo->query("SELECT COUNT(*) FROM slots WHERE vehicle_type = 'car' AND status = 'occupied'")->fetchColumn();
$bikeTotal = (int) $pdo->query("SELECT COUNT(*) FROM slots WHERE vehicle_type = 'bike'")->fetchColumn();
$bikeOcc   = (int) $pdo->query("SELECT COUNT(*) FROM slots WHERE vehicle_type = 'bike' AND status = 'occupied'")->fetchColumn();

// Today's stats
$todayRevenue = $pdo->query(
    "SELECT COALESCE(SUM(fee), 0) FROM vehicles WHERE status = 'exited' AND DATE(exit_time) = CURDATE()"
)->fetchColumn();

$todayEntries = (int) $pdo->query(
    "SELECT COUNT(*) FROM vehicles WHERE DATE(entry_time) = CURDATE()"
)->fetchColumn();

$avgDuration = $pdo->query(
    "SELECT COALESCE(AVG(duration_minutes), 0) FROM vehicles WHERE status = 'exited' AND DATE(exit_time) = CURDATE()"
)->fetchColumn();

// Recent activity
$recent = $pdo->query(
    "SELECT v.vehicle_number, v.vehicle_type, s.slot_number, v.entry_time, v.exit_time, v.status, v.fee
     FROM vehicles v JOIN slots s ON v.slot_id = s.id
     ORDER BY v.id DESC LIMIT 8"
)->fetchAll();

// Previous login, for at-a-glance "who's been using this" visibility
$lastLogin = $pdo->query(
    "SELECT username, ip_address, created_at FROM activity_log
     WHERE action = 'login_success' ORDER BY id DESC LIMIT 1 OFFSET 1"
)->fetch();

$recentFailedLogins = (int) $pdo->query(
    "SELECT COUNT(*) FROM activity_log WHERE action = 'login_failed' AND created_at >= NOW() - INTERVAL 24 HOUR"
)->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<?php if ($lastLogin || $recentFailedLogins > 0): ?>
<div class="alert <?= $recentFailedLogins > 0 ? 'alert-error' : 'alert-success' ?>" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
    <span>
        <?php if ($lastLogin): ?>
            Last sign-in before this one: <strong><?= htmlspecialchars($lastLogin['username']) ?></strong>
            from <?= htmlspecialchars($lastLogin['ip_address']) ?> at <?= date('d M, H:i', strtotime($lastLogin['created_at'])) ?>.
        <?php else: ?>
            This is the first recorded sign-in.
        <?php endif; ?>
        <?php if ($recentFailedLogins > 0): ?>
            &nbsp;⚠ <?= $recentFailedLogins ?> failed login attempt<?= $recentFailedLogins > 1 ? 's' : '' ?> in the last 24 hours.
        <?php endif; ?>
    </span>
    <a href="<?= BASE_URL ?>/activity_log.php" class="btn btn-outline" style="padding:4px 12px; font-size:12px;">View activity log</a>
</div>
<?php endif; ?>

<div class="signboard">
    <div class="signboard-cell">
        <div class="signboard-label">TOTAL SLOTS</div>
        <div class="signboard-value white meter"><?= $totalSlots ?></div>
    </div>
    <div class="signboard-cell">
        <div class="signboard-label">OCCUPIED</div>
        <div class="signboard-value red meter"><?= $occupiedSlots ?></div>
    </div>
    <div class="signboard-cell">
        <div class="signboard-label">AVAILABLE</div>
        <div class="signboard-value green meter"><?= $availableSlots ?></div>
    </div>
    <div class="signboard-cell">
        <div class="signboard-label">TODAY'S REVENUE</div>
        <div class="signboard-value meter">₹<?= number_format($todayRevenue, 2) ?></div>
    </div>
</div>

<div class="grid grid-3">
    <div class="card">
        <div class="card-title">Car slots</div>
        <div class="card-value meter"><?= $carOcc ?> / <?= $carTotal ?></div>
        <div class="text-soft" style="font-size:12px; margin-top:4px;">occupied / total</div>
    </div>
    <div class="card">
        <div class="card-title">Bike slots</div>
        <div class="card-value meter"><?= $bikeOcc ?> / <?= $bikeTotal ?></div>
        <div class="text-soft" style="font-size:12px; margin-top:4px;">occupied / total</div>
    </div>
    <div class="card">
        <div class="card-title">Avg. duration today</div>
        <div class="card-value meter"><?= round($avgDuration) ?> min</div>
        <div class="text-soft" style="font-size:12px; margin-top:4px;"><?= $todayEntries ?> vehicles entered today</div>
    </div>
</div>

<div class="section-title">Recent activity</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Vehicle No.</th>
                <th>Type</th>
                <th>Slot</th>
                <th>Entry</th>
                <th>Exit</th>
                <th>Fee</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$recent): ?>
                <tr><td colspan="7" class="text-soft">No activity yet. Log a vehicle entry to get started.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td class="meter"><?= htmlspecialchars($r['vehicle_number']) ?></td>
                    <td><?= ucfirst($r['vehicle_type']) ?></td>
                    <td class="meter"><?= htmlspecialchars($r['slot_number']) ?></td>
                    <td><?= date('d M, H:i', strtotime($r['entry_time'])) ?></td>
                    <td><?= $r['exit_time'] ? date('d M, H:i', strtotime($r['exit_time'])) : '—' ?></td>
                    <td><?= $r['fee'] !== null ? '₹' . number_format($r['fee'], 2) : '—' ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
