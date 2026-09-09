<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Activity Log';
$activeNav = 'activity';

$actionFilter = $_GET['action'] ?? 'all';

$sql = "SELECT username, action, ip_address, created_at FROM activity_log WHERE 1=1";
$params = [];

if (in_array($actionFilter, ['login_success', 'login_failed', 'logout', 'register'], true)) {
    $sql .= " AND action = ?";
    $params[] = $actionFilter;
}

$sql .= " ORDER BY id DESC LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$failedCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM activity_log WHERE action = 'login_failed' AND created_at >= NOW() - INTERVAL 24 HOUR"
)->fetchColumn();

$lastSuccess = $pdo->query(
    "SELECT username, ip_address, created_at FROM activity_log
     WHERE action = 'login_success' ORDER BY id DESC LIMIT 1 OFFSET 1"
)->fetch();

$badgeClass = [
    'login_success' => 'badge-available',
    'login_failed'  => 'badge-occupied',
    'logout'        => 'badge-parked',
    'register'      => 'badge-exited',
];
$actionLabel = [
    'login_success' => 'Login',
    'login_failed'  => 'Failed login',
    'logout'        => 'Logout',
    'register'      => 'Registered',
];

require __DIR__ . '/includes/header.php';
?>

<div class="grid grid-2">
    <div class="card">
        <div class="card-title">Failed logins (last 24h)</div>
        <div class="card-value meter" style="color: <?= $failedCount > 0 ? 'var(--signal-red)' : 'var(--ink)' ?>;">
            <?= $failedCount ?>
        </div>
    </div>
    <div class="card">
        <div class="card-title">Previous sign-in</div>
        <?php if ($lastSuccess): ?>
            <div class="card-value meter" style="font-size:18px;">
                <?= htmlspecialchars($lastSuccess['username']) ?> · <?= date('d M, H:i', strtotime($lastSuccess['created_at'])) ?>
            </div>
            <div class="text-soft" style="font-size:12px; margin-top:4px;">from <?= htmlspecialchars($lastSuccess['ip_address']) ?></div>
        <?php else: ?>
            <div class="card-value meter" style="font-size:18px;">This is your first login</div>
        <?php endif; ?>
    </div>
</div>

<div class="section-title">Login &amp; logout history</div>

<form method="get" action="" class="flex gap-8" style="margin-bottom:20px;">
    <select name="action" style="padding:10px 12px; border:1px solid var(--line); border-radius:2px; font-size:14px;">
        <option value="all" <?= $actionFilter === 'all' ? 'selected' : '' ?>>All activity</option>
        <option value="login_success" <?= $actionFilter === 'login_success' ? 'selected' : '' ?>>Successful logins</option>
        <option value="login_failed" <?= $actionFilter === 'login_failed' ? 'selected' : '' ?>>Failed logins</option>
        <option value="logout" <?= $actionFilter === 'logout' ? 'selected' : '' ?>>Logouts</option>
        <option value="register" <?= $actionFilter === 'register' ? 'selected' : '' ?>>Registrations</option>
    </select>
    <button type="submit" class="btn btn-outline">Filter</button>
    <a href="" class="btn btn-outline">Reset</a>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Action</th>
                <th>IP address</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="4" class="text-soft">No activity recorded yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="meter"><?= htmlspecialchars($r['username']) ?></td>
                    <td><span class="badge <?= $badgeClass[$r['action']] ?>"><?= $actionLabel[$r['action']] ?></span></td>
                    <td class="meter"><?= htmlspecialchars($r['ip_address'] ?? '—') ?></td>
                    <td><?= date('d M Y, H:i:s', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
