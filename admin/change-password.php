<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $username = $_SESSION['admin_username'];

        if (!verify_admin_login($username, $current)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            if (update_admin_password($username, $new)) {
                $success = true;
            } else {
                $errors[] = 'Could not update the password. Check file permissions on data/admin-users.json.';
            }
        }
    }
}

$csrf = csrf_token();
$pageTitle = 'Change Password';
$activeNav = 'password';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <h1 class="page-title">Change Password</h1>
    <p class="page-sub">Update the password for account "<?= e($_SESSION['admin_username']) ?>".</p>

    <?php if ($success): ?>
        <div class="alert alert-success">Password updated.</div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <label for="current_password">Current password</label>
        <input type="password" id="current_password" name="current_password" required>

        <label for="new_password">New password<span class="hint">At least 8 characters.</span></label>
        <input type="password" id="new_password" name="new_password" required minlength="8">

        <label for="confirm_password">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">

        <div class="form-actions">
            <button type="submit" class="btn">Update Password</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
