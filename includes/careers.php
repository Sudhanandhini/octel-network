<?php
require_once __DIR__ . '/content.php';

date_default_timezone_set('Asia/Kolkata');

define('JOBS_FILE', __DIR__ . '/../data/jobs.json');
define('APPLICATIONS_FILE', __DIR__ . '/../data/applications.json');
define('RESUME_DIR', __DIR__ . '/../data/uploads/resumes');
define('RESUME_MAX_BYTES', 5 * 1024 * 1024);

/* ---------- Job openings ---------- */

function load_jobs(): array {
    return load_items(JOBS_FILE);
}

function save_jobs(array $jobs): bool {
    return save_items(JOBS_FILE, $jobs);
}

function get_job(string $slug): ?array {
    return get_item(JOBS_FILE, $slug);
}

function get_published_jobs(): array {
    return get_published_items(JOBS_FILE);
}

function job_types(): array {
    return ['Full-time', 'Part-time', 'Contract', 'Internship'];
}

/* ---------- Applications ---------- */

function load_applications(): array {
    $apps = load_items(APPLICATIONS_FILE);
    usort($apps, function ($a, $b) {
        return strcmp($b['applied_at'] ?? '', $a['applied_at'] ?? '');
    });
    return $apps;
}

function save_applications(array $apps): bool {
    return save_items(APPLICATIONS_FILE, $apps);
}

function get_application(string $id): ?array {
    foreach (load_items(APPLICATIONS_FILE) as $app) {
        if (($app['id'] ?? '') === $id) return $app;
    }
    return null;
}

/** status key => label */
function application_statuses(): array {
    return [
        'pending'     => 'Pending',
        'reviewed'    => 'Reviewed',
        'shortlisted' => 'Shortlisted',
        'rejected'    => 'Rejected',
    ];
}

function count_applications_by_status(array $apps): array {
    $counts = array_fill_keys(array_keys(application_statuses()), 0);
    foreach ($apps as $app) {
        $s = $app['status'] ?? 'pending';
        if (isset($counts[$s])) $counts[$s]++;
    }
    return $counts;
}

function resume_allowed_extensions(): array {
    return ['pdf', 'doc', 'docx'];
}

/**
 * Validate and store a submitted application for $job.
 * Returns ['ok' => true] or ['ok' => false, 'errors' => [...], 'old' => [...]].
 */
function submit_application(array $job, array $post, array $files): array {
    $old = [
        'name'          => trim($post['name'] ?? ''),
        'email'         => trim($post['email'] ?? ''),
        'phone'         => trim($post['phone'] ?? ''),
        'qualification' => trim($post['qualification'] ?? ''),
        'experience'    => trim($post['experience'] ?? ''),
        'cover_letter'  => trim($post['cover_letter'] ?? ''),
    ];
    $errors = [];

    // Honeypot: real users never fill this hidden field.
    if (trim($post['website'] ?? '') !== '') {
        return ['ok' => true];
    }

    if ($old['name'] === '' || mb_strlen($old['name']) > 120) $errors[] = 'Please enter your full name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone'])) $errors[] = 'Please enter a valid phone number.';
    if (mb_strlen($old['qualification']) > 120) $errors[] = 'Qualification is too long.';
    if (mb_strlen($old['experience']) > 60) $errors[] = 'Experience is too long.';
    if (mb_strlen($old['cover_letter']) > 5000) $errors[] = 'Cover letter is too long (5000 characters max).';

    $file = $files['resume'] ?? null;
    $ext = '';
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please upload your CV / resume.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Resume upload failed. Please try again.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, resume_allowed_extensions(), true)) {
            $errors[] = 'Resume must be a PDF, DOC or DOCX file.';
        } elseif ($file['size'] > RESUME_MAX_BYTES) {
            $errors[] = 'Resume must be 5 MB or smaller.';
        }
    }

    if ($errors) return ['ok' => false, 'errors' => $errors, 'old' => $old];

    if (!is_dir(RESUME_DIR) && !mkdir(RESUME_DIR, 0755, true)) {
        return ['ok' => false, 'errors' => ['Could not save your resume. Please try again later.'], 'old' => $old];
    }

    $id = date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $storedName = $id . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], RESUME_DIR . '/' . $storedName)) {
        return ['ok' => false, 'errors' => ['Could not save your resume. Please try again later.'], 'old' => $old];
    }

    $apps = load_items(APPLICATIONS_FILE);
    $apps[] = $old + [
        'id'              => $id,
        'job_slug'        => $job['slug'],
        'job_title'       => $job['title'],
        'resume_file'     => $storedName,
        'resume_original' => basename($file['name']),
        'status'          => 'pending',
        'notes'           => '',
        'applied_at'      => date('c'),
    ];
    if (!save_applications($apps)) {
        return ['ok' => false, 'errors' => ['Could not submit your application. Please try again later.'], 'old' => $old];
    }
    return ['ok' => true];
}

function delete_application(string $id): bool {
    $apps = load_items(APPLICATIONS_FILE);
    $kept = [];
    foreach ($apps as $app) {
        if (($app['id'] ?? '') === $id) {
            $path = RESUME_DIR . '/' . basename($app['resume_file'] ?? '');
            if (!empty($app['resume_file']) && is_file($path)) @unlink($path);
            continue;
        }
        $kept[] = $app;
    }
    return save_applications($kept);
}

function format_applied_at(?string $iso): string {
    if (!$iso) return '';
    $ts = strtotime($iso);
    return $ts ? date('j F Y \a\t h:i a', $ts) : $iso;
}
