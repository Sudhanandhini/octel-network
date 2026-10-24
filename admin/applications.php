<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/careers.php';
require_once __DIR__ . '/includes/search.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (delete_application((string)($_POST['id'] ?? ''))) {
        header('Location: applications.php?deleted=1');
        exit;
    } else {
        $error = 'Could not delete the application.';
    }
}

$statuses = application_statuses();
$allApps = load_applications();

$jobFilter = (string)($_GET['job'] ?? '');
if ($jobFilter !== '') {
    $allApps = array_values(array_filter($allApps, function ($a) use ($jobFilter) {
        return ($a['job_slug'] ?? '') === $jobFilter;
    }));
}
$jobApps = $allApps;
$q = admin_search_query();
$allApps = admin_search_filter($allApps, $q, ['name', 'email', 'phone', 'qualification', 'experience', 'job_title', 'cover_letter', 'notes']);
$counts = count_applications_by_status($allApps);

$statusFilter = (string)($_GET['status'] ?? '');
if (!isset($statuses[$statusFilter])) $statusFilter = '';
$apps = $statusFilter === '' ? $allApps : array_values(array_filter($allApps, function ($a) use ($statusFilter) {
    return ($a['status'] ?? 'pending') === $statusFilter;
}));

function filter_url(string $status, string $job): string {
    $params = array_filter(['status' => $status, 'job' => $job, 'q' => admin_search_query()], 'strlen');
    return 'applications.php' . ($params ? '?' . http_build_query($params) : '');
}

$jobTitle = '';
if ($jobFilter !== '') {
    $j = get_job($jobFilter);
    $jobTitle = $j['title'] ?? ($jobApps[0]['job_title'] ?? $jobFilter);
}

$csrf = csrf_token();
$pageTitle = 'Careers';
$activeNav = 'applications';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <div class="card-head">
        <div>
            <h1 class="page-title">Job Applications</h1>
            <p class="page-sub">
                Total applications: <?= count($jobApps) ?>
                <?php if ($jobTitle !== ''): ?>
                    for <strong><?= e($jobTitle) ?></strong> &middot; <a class="link-edit" href="<?= e(filter_url($statusFilter, '')) ?>">Show all jobs</a>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Application deleted.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php render_admin_search($q, ['status' => $statusFilter, 'job' => $jobFilter], 'Search by name, email, phone, job, qualification…'); ?>
    <?php if ($q !== ''): ?>
        <p class="search-note"><?= count($allApps) ?> result<?= count($allApps) === 1 ? '' : 's' ?> for “<?= e($q) ?>”</p>
    <?php endif; ?>

    <div class="filter-tabs">
        <a href="<?= e(filter_url('', $jobFilter)) ?>" class="<?= $statusFilter === '' ? 'active' : '' ?>">All Applications (<?= count($allApps) ?>)</a>
        <?php foreach ($statuses as $key => $label): ?>
            <a href="<?= e(filter_url($key, $jobFilter)) ?>" class="<?= $statusFilter === $key ? 'active' : '' ?>"><?= e($label) ?> (<?= $counts[$key] ?>)</a>
        <?php endforeach; ?>
    </div>

    <?php if (!$apps): ?>
        <div class="empty-state">No applications found.</div>
    <?php endif; ?>

    <?php foreach ($apps as $app): $status = $app['status'] ?? 'pending'; ?>
    <div class="list-item">
        <div style="flex:1;min-width:260px">
            <h3><a href="application.php?id=<?= e($app['id']) ?>"><?= e($app['name']) ?></a></h3>
            <p class="item-sub">Applied for: <?= e($app['job_title'] ?? '') ?></p>
            <div class="item-meta">
                <span><i class="ri-mail-line"></i><?= e($app['email']) ?></span>
                <span><i class="ri-phone-line"></i><?= e($app['phone']) ?></span>
                <?php if (!empty($app['qualification'])): ?><span><i class="ri-graduation-cap-line"></i><?= e($app['qualification']) ?></span><?php endif; ?>
                <?php if (!empty($app['experience'])): ?><span><i class="ri-briefcase-line"></i><?= e($app['experience']) ?></span><?php endif; ?>
                <span><i class="ri-calendar-line"></i>Applied on <?= e(format_applied_at($app['applied_at'] ?? '')) ?></span>
            </div>
        </div>
        <div class="item-actions">
            <span class="badge badge-<?= e($status) ?>"><?= e($statuses[$status] ?? $status) ?></span>
            <a href="application.php?id=<?= e($app['id']) ?>" class="btn btn-sm"><i class="ri-eye-line"></i> View Details</a>
            <form method="post" onsubmit="return confirm('Delete this application and its resume? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= e($app['id']) ?>">
                <button class="btn btn-sm btn-danger-outline" title="Delete"><i class="ri-delete-bin-line"></i></button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
