<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Settings';
$activeNav = 'settings';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rates') {
    $carRate  = (float) ($_POST['car_rate_per_hour'] ?? 0);
    $bikeRate = (float) ($_POST['bike_rate_per_hour'] ?? 0);
    $grace    = (int) ($_POST['grace_minutes'] ?? 0);

    $pdo->prepare(
        "UPDATE settings SET car_rate_per_hour = ?, bike_rate_per_hour = ?, grace_minutes = ? WHERE id = 1"
    )->execute([$carRate, $bikeRate, $grace]);

    $message = 'Billing settings updated.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current, $admin['password_hash'])) {
        $message = 'Current password is incorrect.';
        $messageType = 'error';
    } elseif (strlen($new) < 6) {
        $message = 'New password must be at least 6 characters.';
        $messageType = 'error';
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([$newHash, $_SESSION['admin_id']]);
        $message = 'Password updated.';
        $messageType = 'success';
    }
}

$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="section-title">Billing rates</div>
<div class="form-card">
    <form method="post" action="">
        <input type="hidden" name="action" value="rates">
        <div class="field">
            <label for="car_rate_per_hour">Car rate (₹ / hour)</label>
            <input type="number" step="0.01" min="0" id="car_rate_per_hour" name="car_rate_per_hour"
                   value="<?= htmlspecialchars($settings['car_rate_per_hour']) ?>" required>
        </div>
        <div class="field">
            <label for="bike_rate_per_hour">Bike rate (₹ / hour)</label>
            <input type="number" step="0.01" min="0" id="bike_rate_per_hour" name="bike_rate_per_hour"
                   value="<?= htmlspecialchars($settings['bike_rate_per_hour']) ?>" required>
        </div>
        <div class="field">
            <label for="grace_minutes">Grace period (minutes, free of charge)</label>
            <input type="number" min="0" id="grace_minutes" name="grace_minutes"
                   value="<?= htmlspecialchars($settings['grace_minutes']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary w-full">Save billing settings</button>
    </form>
</div>

<div class="section-title">Change password</div>
<div class="form-card">
    <form method="post" action="">
        <input type="hidden" name="action" value="password">
        <div class="field">
            <label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="field">
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password" minlength="6" required>
        </div>
        <button type="submit" class="btn btn-primary w-full">Update password</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
