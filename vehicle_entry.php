<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Vehicle Entry';
$activeNav = 'entry';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleNumber = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $vehicleType   = $_POST['vehicle_type'] ?? '';

    if ($vehicleNumber === '' || !in_array($vehicleType, ['car', 'bike'], true)) {
        $message = 'Please enter a valid vehicle number and select a type.';
        $messageType = 'error';
    } else {
        // Prevent double entry for a vehicle already parked
        $check = $pdo->prepare("SELECT id FROM vehicles WHERE vehicle_number = ? AND status = 'parked'");
        $check->execute([$vehicleNumber]);

        if ($check->fetch()) {
            $message = "Vehicle {$vehicleNumber} is already parked.";
            $messageType = 'error';
        } else {
            // Find the next available slot of the requested type
            $slotStmt = $pdo->prepare(
                "SELECT id, slot_number FROM slots WHERE vehicle_type = ? AND status = 'available' ORDER BY slot_number LIMIT 1"
            );
            $slotStmt->execute([$vehicleType]);
            $slot = $slotStmt->fetch();

            if (!$slot) {
                $message = ucfirst($vehicleType) . ' parking is full. No slots available.';
                $messageType = 'error';
            } else {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("UPDATE slots SET status = 'occupied' WHERE id = ?")->execute([$slot['id']]);
                    $pdo->prepare(
                        "INSERT INTO vehicles (slot_id, vehicle_number, vehicle_type, entry_time, status)
                         VALUES (?, ?, ?, NOW(), 'parked')"
                    )->execute([$slot['id'], $vehicleNumber, $vehicleType]);
                    $pdo->commit();

                    $message = "Vehicle {$vehicleNumber} checked in to slot {$slot['slot_number']}.";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = 'Something went wrong. Please try again.';
                    $messageType = 'error';
                }
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
        <div class="field">
            <label for="vehicle_type">Vehicle type</label>
            <select id="vehicle_type" name="vehicle_type" required>
                <option value="">Select type</option>
                <option value="car">Car</option>
                <option value="bike">Bike</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-full">Check in vehicle</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
