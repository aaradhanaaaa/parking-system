<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Vehicle Log';
$activeNav = 'log';

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$sql = "SELECT v.vehicle_number, v.vehicle_type, s.slot_number, v.entry_time, v.exit_time, v.duration_minutes, v.fee, v.status
        FROM vehicles v JOIN slots s ON v.slot_id = s.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND v.vehicle_number LIKE ?";
    $params[] = '%' . $search . '%';
}

if (in_array($statusFilter, ['parked', 'exited'], true)) {
    $sql .= " AND v.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY v.id DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<form method="get" action="" class="flex gap-8" style="margin-bottom:20px; flex-wrap:wrap;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search vehicle number"
           style="padding:10px 12px; border:1px solid var(--line); border-radius:2px; font-size:14px; min-width:220px;">
    <select name="status" style="padding:10px 12px; border:1px solid var(--line); border-radius:2px; font-size:14px;">
        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All statuses</option>
        <option value="parked" <?= $statusFilter === 'parked' ? 'selected' : '' ?>>Currently parked</option>
        <option value="exited" <?= $statusFilter === 'exited' ? 'selected' : '' ?>>Exited</option>
    </select>
    <button type="submit" class="btn btn-outline">Filter</button>
    <a href="" class="btn btn-outline">Reset</a>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Vehicle No.</th>
                <th>Type</th>
                <th>Slot</th>
                <th>Entry</th>
                <th>Exit</th>
                <th>Duration</th>
                <th>Fee</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8" class="text-soft">No records match your filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="meter"><?= htmlspecialchars($r['vehicle_number']) ?></td>
                    <td><?= ucfirst($r['vehicle_type']) ?></td>
                    <td class="meter"><?= htmlspecialchars($r['slot_number']) ?></td>
                    <td><?= date('d M, H:i', strtotime($r['entry_time'])) ?></td>
                    <td><?= $r['exit_time'] ? date('d M, H:i', strtotime($r['exit_time'])) : '—' ?></td>
                    <td><?= $r['duration_minutes'] !== null ? $r['duration_minutes'] . ' min' : '—' ?></td>
                    <td><?= $r['fee'] !== null ? '₹' . number_format($r['fee'], 2) : '—' ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
