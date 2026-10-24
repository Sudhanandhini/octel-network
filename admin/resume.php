<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/careers.php';
require_login();

$app = get_application((string)($_GET['id'] ?? ''));
$path = $app ? RESUME_DIR . '/' . basename($app['resume_file'] ?? '') : '';
if (!$app || empty($app['resume_file']) || !is_file($path)) {
    http_response_code(404);
    exit('Resume not found.');
}

$types = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$downloadName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $app['name'] . ' - Resume.' . $ext);

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment') . '; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
