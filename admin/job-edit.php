<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/careers.php';
require_login();

$slug = (string)($_GET['slug'] ?? '');
$isNew = $slug === '';
$job = $isNew ? null : get_job($slug);
if (!$isNew && !$job) {
    header('Location: careers.php');
    exit;
}

$job = $job ?? [
    'title' => '', 'department' => '', 'location' => 'Bengaluru', 'job_type' => 'Full-time',
    'experience' => '', 'summary' => '', 'overview' => '', 'responsibilities' => [],
    'required' => [], 'preferred' => [], 'offer' => '', 'published' => true,
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job = array_merge($job, [
        'title'            => trim($_POST['title'] ?? ''),
        'department'       => trim($_POST['department'] ?? ''),
        'location'         => trim($_POST['location'] ?? ''),
        'job_type'         => in_array($_POST['job_type'] ?? '', job_types(), true) ? $_POST['job_type'] : 'Full-time',
        'experience'       => trim($_POST['experience'] ?? ''),
        'summary'          => trim($_POST['summary'] ?? ''),
        'overview'         => trim($_POST['overview'] ?? ''),
        'responsibilities' => parse_lines($_POST['responsibilities'] ?? ''),
        'required'         => parse_lines($_POST['required'] ?? ''),
        'preferred'        => parse_lines($_POST['preferred'] ?? ''),
        'offer'            => trim($_POST['offer'] ?? ''),
        'published'        => !empty($_POST['published']),
    ]);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($job['title'] === '') {
        $error = 'Job title is required.';
    } else {
        $jobs = load_jobs();
        if ($isNew) {
            $job['slug'] = unique_slug(slugify($job['title']), array_column($jobs, 'slug'));
            $job['created_at'] = date('c');
            $jobs[] = $job;
        } else {
            foreach ($jobs as $i => $existing) {
                if ($existing['slug'] === $slug) $jobs[$i] = $job;
            }
        }
        if (save_jobs($jobs)) {
            header('Location: careers.php?saved=1');
            exit;
        }
        $error = 'Could not save. Check that data/jobs.json is writable.';
    }
}

$csrf = csrf_token();
$pageTitle = $isNew ? 'Add Job Opening' : 'Edit Job Opening';
$activeNav = 'jobs';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <div class="card-head">
        <div>
            <h1 class="page-title"><?= $isNew ? 'New Job Opening' : e($job['title']) ?></h1>
            <p class="page-sub">
                <?php if ($isNew): ?>
                    Fill in the details below. Active openings appear on careers.html.
                <?php else: ?>
                    <a class="link-edit" href="../career-details.html?slug=<?= e($slug) ?>" target="_blank">View on site &rarr;</a>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <label for="title">Job Title *</label>
        <input type="text" id="title" name="title" value="<?= e($job['title']) ?>" required>

        <div class="form-grid">
            <div>
                <label for="department">Department</label>
                <input type="text" id="department" name="department" value="<?= e($job['department']) ?>" placeholder="e.g. Engineering">
            </div>
            <div>
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?= e($job['location']) ?>" placeholder="e.g. Bengaluru">
            </div>
            <div>
                <label for="job_type">Job Type</label>
                <select id="job_type" name="job_type">
                    <?php foreach (job_types() as $t): ?>
                        <option <?= $job['job_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="experience">Experience</label>
                <input type="text" id="experience" name="experience" value="<?= e($job['experience']) ?>" placeholder="e.g. 2-5 years">
            </div>
        </div>

        <label for="summary">Short Description <span class="hint">Shown on the careers listing.</span></label>
        <textarea id="summary" name="summary" rows="3"><?= e($job['summary']) ?></textarea>

        <label for="overview">Job Overview <span class="hint">Opening paragraph(s) on the job detail page. Leave a blank line between paragraphs.</span></label>
        <textarea id="overview" name="overview" rows="5"><?= e($job['overview']) ?></textarea>

        <label for="responsibilities">Key Responsibilities <span class="hint">One per line.</span></label>
        <textarea id="responsibilities" name="responsibilities" rows="6"><?= e(implode("\n", $job['responsibilities'])) ?></textarea>

        <div class="form-grid">
            <div>
                <label for="required">Required Qualifications <span class="hint">One per line.</span></label>
                <textarea id="required" name="required" rows="5"><?= e(implode("\n", $job['required'])) ?></textarea>
            </div>
            <div>
                <label for="preferred">Preferred Qualifications <span class="hint">One per line.</span></label>
                <textarea id="preferred" name="preferred" rows="5"><?= e(implode("\n", $job['preferred'])) ?></textarea>
            </div>
        </div>

        <label for="offer">What We Offer <span class="hint">Leave a blank line between paragraphs.</span></label>
        <textarea id="offer" name="offer" rows="4"><?= e($job['offer']) ?></textarea>

        <div class="checkbox-row">
            <input type="checkbox" id="published" name="published" value="1" <?= !empty($job['published']) ? 'checked' : '' ?>>
            <label for="published" style="margin:0">Active (show on careers page)</label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn"><?= $isNew ? 'Create Opening' : 'Save Changes' ?></button>
            <a href="careers.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
