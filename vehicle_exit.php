<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Vehicle Exit';
$activeNav = 'exit';

$message = '';
$messageType = '';
$receipt = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleNumber = strtoupper(trim($_POST['vehicle_number'] ?? ''));

    if ($vehicleNumber === '') {
        $message = 'Please enter a vehicle number.';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare(
            "SELECT v.id, v.slot_id, v.entry_time, v.vehicle_type, s.slot_number
             FROM vehicles v JOIN slots s ON v.slot_id = s.id
             WHERE v.vehicle_number = ? AND v.status = 'parked'"
        );
        $stmt->execute([$vehicleNumber]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            $message = "No parked vehicle found with number {$vehicleNumber}.";
            $messageType = 'error';
        } else {
            $settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
            $rate = $vehicle['vehicle_type'] === 'car'
                ? (float) $settings['car_rate_per_hour']
                : (float) $settings['bike_rate_per_hour'];
            $graceMinutes = (int) $settings['grace_minutes'];

            $entryTime = new DateTime($vehicle['entry_time']);
            $exitTime  = new DateTime();
            $durationMinutes = max(1, (int) (($exitTime->getTimestamp() - $entryTime->getTimestamp()) / 60));

            if ($durationMinutes <= $graceMinutes) {
                $fee = 0.00;
            } else {
                $billableHours = (int) ceil($durationMinutes / 60);
                $fee = $billableHours * $rate;
            }

            $pdo->beginTransaction();
            try {
                $pdo->prepare(
                    "UPDATE vehicles SET exit_time = ?, duration_minutes = ?, fee = ?, status = 'exited' WHERE id = ?"
                )->execute([$exitTime->format('Y-m-d H:i:s'), $durationMinutes, $fee, $vehicle['id']]);

                $pdo->prepare("UPDATE slots SET status = 'available' WHERE id = ?")->execute([$vehicle['slot_id']]);

                $pdo->commit();

                $receipt = [
                    'vehicle_number' => $vehicleNumber,
                    'slot_number'    => $vehicle['slot_number'],
                    'entry_time'     => $entryTime,
                    'exit_time'      => $exitTime,
                    'duration'       => $durationMinutes,
                    'fee'            => $fee,
                ];
                $message = "Vehicle {$vehicleNumber} checked out from slot {$vehicle['slot_number']}.";
                $messageType = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Something went wrong. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="post" action="">
        <div class="field">
            <label for="vehicle_number">Vehicle number</label>
            <input type="text" id="vehicle_number" name="vehicle_number" placeholder="e.g. WB 05 AB 1234" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-full">Check out vehicle</button>
    </form>
</div>

<?php if ($receipt): ?>
<div class="section-title">Receipt</div>
<div class="card" style="max-width:480px;">
    <table>
        <tr><td class="text-soft">Vehicle</td><td class="meter"><?= htmlspecialchars($receipt['vehicle_number']) ?></td></tr>
        <tr><td class="text-soft">Slot</td><td class="meter"><?= htmlspecialchars($receipt['slot_number']) ?></td></tr>
        <tr><td class="text-soft">Entry</td><td><?= $receipt['entry_time']->format('d M Y, H:i') ?></td></tr>
        <tr><td class="text-soft">Exit</td><td><?= $receipt['exit_time']->format('d M Y, H:i') ?></td></tr>
        <tr><td class="text-soft">Duration</td><td class="meter"><?= $receipt['duration'] ?> min</td></tr>
        <tr><td class="text-soft">Fee</td><td class="meter" style="font-weight:700;">₹<?= number_format($receipt['fee'], 2) ?></td></tr>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
