<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/careers.php';
require_once __DIR__ . '/includes/search.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $slug = (string)($_POST['slug'] ?? '');
        $action = (string)($_POST['action'] ?? '');
        $jobs = load_jobs();
        foreach ($jobs as $i => $job) {
            if (($job['slug'] ?? '') !== $slug) continue;
            if ($action === 'delete') {
                array_splice($jobs, $i, 1);
            } elseif ($action === 'toggle') {
                $jobs[$i]['published'] = empty($job['published']);
            }
            break;
        }
        if (save_jobs($jobs)) {
            header('Location: careers.php?saved=1');
            exit;
        }
        $error = 'Could not save changes. Check that data/jobs.json is writable.';
    }
}

$allJobs = load_jobs();
$q = admin_search_query();
$jobs = admin_search_filter($allJobs, $q, ['title', 'department', 'location', 'job_type', 'experience', 'summary', 'overview']);
$appCounts = [];
foreach (load_applications() as $app) {
    $s = $app['job_slug'] ?? '';
    $appCounts[$s] = ($appCounts[$s] ?? 0) + 1;
}
$csrf = csrf_token();
$pageTitle = 'Careers';
$activeNav = 'jobs';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <div class="card-head">
        <div>
            <h1 class="page-title">Current Openings</h1>
            <p class="page-sub">Total openings: <?= count($allJobs) ?>. Active openings are listed on careers.html.</p>
        </div>
        <a href="job-edit.php" class="btn"><i class="ri-add-line"></i> Add New Opening</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Changes saved.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php render_admin_search($q, [], 'Search by title, department, location, type…'); ?>
    <?php if ($q !== ''): ?>
        <p class="search-note"><?= count($jobs) ?> result<?= count($jobs) === 1 ? '' : 's' ?> for “<?= e($q) ?>”</p>
    <?php endif; ?>

    <?php if (!$jobs): ?>
        <div class="empty-state"><?= $q !== '' ? 'No openings match your search.' : 'No job openings yet. Click "Add New Opening" to create one.' ?></div>
    <?php endif; ?>

    <?php foreach ($jobs as $job): $count = $appCounts[$job['slug']] ?? 0; ?>
    <div class="list-item">
        <div style="flex:1;min-width:260px">
            <h3>
                <a href="job-edit.php?slug=<?= e($job['slug']) ?>"><?= e($job['title']) ?></a>
                <?php if (!empty($job['published'])): ?>
                    <span class="badge badge-live">Active</span>
                <?php else: ?>
                    <span class="badge badge-draft">Inactive</span>
                <?php endif; ?>
            </h3>
            <?php if (!empty($job['summary'])): ?>
                <p class="item-sub"><?= e(mb_strimwidth($job['summary'], 0, 180, '…')) ?></p>
            <?php endif; ?>
            <div class="item-meta">
                <?php if (!empty($job['department'])): ?><span><i class="ri-building-line"></i><?= e($job['department']) ?></span><?php endif; ?>
                <?php if (!empty($job['location'])): ?><span><i class="ri-map-pin-line"></i><?= e($job['location']) ?></span><?php endif; ?>
                <?php if (!empty($job['job_type'])): ?><span><i class="ri-time-line"></i><?= e($job['job_type']) ?></span><?php endif; ?>
                <?php if (!empty($job['experience'])): ?><span><i class="ri-medal-line"></i><?= e($job['experience']) ?></span><?php endif; ?>
                <span><i class="ri-user-line"></i><a class="link-edit" href="applications.php?job=<?= e($job['slug']) ?>"><?= $count ?> application<?= $count === 1 ? '' : 's' ?></a></span>
            </div>
        </div>
        <div class="item-actions">
            <a href="job-edit.php?slug=<?= e($job['slug']) ?>" class="btn btn-sm"><i class="ri-pencil-line"></i> Edit</a>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($job['slug']) ?>">
                <input type="hidden" name="action" value="toggle">
                <button class="btn btn-sm btn-secondary"><?= !empty($job['published']) ? 'Deactivate' : 'Activate' ?></button>
            </form>
            <form method="post" onsubmit="return confirm('Delete this job opening? Existing applications will be kept.');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($job['slug']) ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-sm btn-danger-outline"><i class="ri-delete-bin-line"></i> Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
