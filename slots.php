<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Slots';
$activeNav = 'slots';

$message = '';
$messageType = '';

// Add a new slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $slotNumber = strtoupper(trim($_POST['slot_number'] ?? ''));
    $vehicleType = $_POST['vehicle_type'] ?? '';

    if ($slotNumber === '' || !in_array($vehicleType, ['car', 'bike'], true)) {
        $message = 'Please provide a slot number and type.';
        $messageType = 'error';
    } else {
        $exists = $pdo->prepare("SELECT id FROM slots WHERE slot_number = ?");
        $exists->execute([$slotNumber]);
        if ($exists->fetch()) {
            $message = "Slot {$slotNumber} already exists.";
            $messageType = 'error';
        } else {
            $pdo->prepare("INSERT INTO slots (slot_number, vehicle_type, status) VALUES (?, ?, 'available')")
                ->execute([$slotNumber, $vehicleType]);
            $message = "Slot {$slotNumber} added.";
            $messageType = 'success';
        }
    }
}

// Delete a slot (only if it has no vehicle history and isn't occupied)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $slotId = (int) ($_POST['slot_id'] ?? 0);
    $historyCheck = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE slot_id = ?");
    $historyCheck->execute([$slotId]);

    if ((int) $historyCheck->fetchColumn() > 0) {
        $message = 'This slot has vehicle history and cannot be deleted.';
        $messageType = 'error';
    } else {
        $pdo->prepare("DELETE FROM slots WHERE id = ? AND status = 'available'")->execute([$slotId]);
        $message = 'Slot removed.';
        $messageType = 'success';
    }
}

$slots = $pdo->query("SELECT * FROM slots ORDER BY vehicle_type, slot_number")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="post" action="">
        <input type="hidden" name="action" value="add">
        <div class="field">
            <label for="slot_number">Slot number</label>
            <input type="text" id="slot_number" name="slot_number" placeholder="e.g. C13" required>
        </div>
        <div class="field">
            <label for="vehicle_type">Type</label>
            <select id="vehicle_type" name="vehicle_type" required>
                <option value="">Select type</option>
                <option value="car">Car</option>
                <option value="bike">Bike</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-full">Add slot</button>
    </form>
</div>

<div class="section-title">All slots (<?= count($slots) ?>)</div>
<div class="slot-grid">
    <?php foreach ($slots as $s): ?>
        <div class="slot-tile <?= $s['status'] ?>">
            <div class="slot-num"><?= htmlspecialchars($s['slot_number']) ?></div>
            <div class="slot-type"><?= ucfirst($s['vehicle_type']) ?></div>
            <?php if ($s['status'] === 'available'): ?>
                <form method="post" action="" style="margin-top:8px;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="slot_id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn-outline" style="font-size:11px; padding:4px 8px; border-radius:2px; cursor:pointer;"
                            data-confirm="Remove slot <?= htmlspecialchars($s['slot_number']) ?>?">Remove</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
