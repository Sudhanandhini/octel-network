<?php
/**
 * AJAX endpoint for contact-form email verification.
 *   POST action=send   email=…          -> emails a 6-digit code
 *   POST action=verify email=… code=…   -> checks the code
 * Responds with JSON: {"ok": bool, "message": "...", "wait"?: seconds}
 */
require_once __DIR__ . '/includes/email-verify.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function otp_respond(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    otp_respond(['ok' => false, 'message' => 'Method not allowed.'], 405);
}
// The custom header can only be set by same-origin scripts (cross-site requests would need
// a CORS preflight we never grant), so other websites can't use this to send emails.
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    otp_respond(['ok' => false, 'message' => 'Bad request.'], 400);
}

$action = $_POST['action'] ?? '';
$email  = (string) ($_POST['email'] ?? '');

if ($action === 'send') {
    otp_respond(otp_send($email));
}
if ($action === 'verify') {
    otp_respond(otp_verify($email, (string) ($_POST['code'] ?? '')));
}
otp_respond(['ok' => false, 'message' => 'Unknown action.'], 400);
