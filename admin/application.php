<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/careers.php';
require_login();

$id = (string)($_GET['id'] ?? '');
$app = get_application($id);
if (!$app) {
    header('Location: applications.php');
    exit;
}

$statuses = application_statuses();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        if (delete_application($id)) {
            header('Location: applications.php?deleted=1');
            exit;
        }
        $error = 'Could not delete the application.';
    } else {
        $newStatus = (string)($_POST['status'] ?? '');
        $apps = load_items(APPLICATIONS_FILE);
        foreach ($apps as $i => $a) {
            if (($a['id'] ?? '') !== $id) continue;
            if (isset($statuses[$newStatus])) $apps[$i]['status'] = $newStatus;
            $apps[$i]['notes'] = trim((string)($_POST['notes'] ?? ''));
            $apps[$i]['updated_at'] = date('c');
        }
        if (save_applications($apps)) {
            header('Location: application.php?id=' . urlencode($id) . '&saved=1');
            exit;
        }
        $error = 'Could not save. Check that data/applications.json is writable.';
    }
}

// First view of a pending application marks it as reviewed.
if (($app['status'] ?? 'pending') === 'pending' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $apps = load_items(APPLICATIONS_FILE);
    foreach ($apps as $i => $a) {
        if (($a['id'] ?? '') === $id) $apps[$i]['status'] = 'reviewed';
    }
    if (save_applications($apps)) $app['status'] = 'reviewed';
}

$status = $app['status'] ?? 'pending';
$csrf = csrf_token();
$pageTitle = 'Careers';
$activeNav = 'applications';
require __DIR__ . '/includes/layout-top.php';
?>
<p style="margin:0 0 16px"><a class="link-edit" href="applications.php">&larr; Back to applications</a></p>

<div class="admin-card">
    <div class="card-head">
        <div>
            <h1 class="page-title"><?= e($app['name']) ?> <span class="badge badge-<?= e($status) ?>" style="vertical-align:middle"><?= e($statuses[$status] ?? $status) ?></span></h1>
            <p class="page-sub">Applied for: <strong><?= e($app['job_title'] ?? '') ?></strong> on <?= e(format_applied_at($app['applied_at'] ?? '')) ?></p>
        </div>
        <?php if (!empty($app['resume_file'])): ?>
            <a href="resume.php?id=<?= e($app['id']) ?>" class="btn"><i class="ri-download-2-line"></i> Download Resume</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Changes saved.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="detail-grid">
        <div><div class="k">Email</div><div class="v"><a href="mailto:<?= e($app['email']) ?>"><?= e($app['email']) ?></a></div></div>
        <div><div class="k">Phone</div><div class="v"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $app['phone'])) ?>"><?= e($app['phone']) ?></a></div></div>
        <div><div class="k">Qualification</div><div class="v"><?= e($app['qualification'] ?: '—') ?></div></div>
        <div><div class="k">Experience</div><div class="v"><?= e($app['experience'] ?: '—') ?></div></div>
        <div><div class="k">Resume</div><div class="v"><?= e($app['resume_original'] ?? '—') ?></div></div>
        <?php if (!empty($app['updated_at'])): ?>
            <div><div class="k">Last Updated</div><div class="v"><?= e(format_applied_at($app['updated_at'])) ?></div></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($app['cover_letter'])): ?>
        <label>Cover Letter</label>
        <div class="cover-letter"><?= e($app['cover_letter']) ?></div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h1 class="page-title">Review</h1>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <label for="status">Status</label>
        <select id="status" name="status">
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="notes">Internal Notes <span class="hint">Only visible to admins.</span></label>
        <textarea id="notes" name="notes" rows="4"><?= e($app['notes'] ?? '') ?></textarea>

        <div class="form-actions">
            <button type="submit" class="btn">Save Review</button>
        </div>
    </form>

    <form method="post" onsubmit="return confirm('Delete this application and its resume? This cannot be undone.');" style="margin-top:14px">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-sm btn-danger-outline"><i class="ri-delete-bin-line"></i> Delete Application</button>
    </form>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
