<?php
/**
 * OCTA chatbot lead endpoint.
 * Saves the details OCTA collects as an enquiry (shown in Admin > Contacts)
 * and emails the Sales or Support desk, like the contact form does.
 * Expects a JSON POST; replies with {"ok": true} or {"ok": false, "error": "..."}.
 */
require_once __DIR__ . '/includes/contacts.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function octa_reply(bool $ok, string $error = '', int $status = 200): void {
    http_response_code($status);
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    octa_reply(false, 'Method not allowed.', 405);
}

// Only accept requests sent from this site's own pages.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
    octa_reply(false, 'Forbidden.', 403);
}

// Simple per-visitor limit: at most 5 chat leads per hour.
if (session_status() === PHP_SESSION_NONE) session_start();
$now = time();
$recent = array_filter($_SESSION['octa_leads'] ?? [], function ($t) use ($now) { return $now - $t < 3600; });
if (count($recent) >= 5) {
    octa_reply(false, 'Too many requests. Please try again later.', 429);
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) octa_reply(false, 'Invalid request.', 400);

// Honeypot field the widget always sends empty.
if (trim((string) ($in['website'] ?? '')) !== '') octa_reply(true);

$field = function (string $key, int $max) use ($in): string {
    $v = trim(preg_replace('/\s+/', ' ', (string) ($in[$key] ?? '')));
    return mb_substr($v, 0, $max);
};
$desk    = $field('desk', 10) === 'Support' ? 'Support' : 'Sales';
$need    = $field('need', 80);
$company = $field('company', 150);
$name    = $field('name', 120);
$size    = $field('size', 120);
$city    = $field('city', 80);
$reach   = $field('reach', 120);

if ($name === '' || $company === '' || $reach === '') {
    octa_reply(false, 'Please share your name, company and a way to reach you.', 422);
}

// Same rules as the chat widget (assets/js/chatbot.js).
if (!preg_match("/^[A-Za-z][A-Za-z .'\\-]*$/", $name) || strlen(preg_replace('/[^A-Za-z]/', '', $name)) < 2 || mb_strlen($name) > 60) {
    octa_reply(false, 'Please enter a valid name, using letters only.', 422);
}
if (mb_strlen($company) < 2 || !preg_match('/[A-Za-z]/', $company) || mb_strlen($company) > 100) {
    octa_reply(false, 'Please enter a valid company name.', 422);
}
if ($size !== '' && !preg_match('/\d/', $size)) {
    octa_reply(false, 'Please include a number of sites or users.', 422);
}
if ($city !== '' && !preg_match('/^[A-Za-z][A-Za-z .\-]{1,49}$/', $city)) {
    octa_reply(false, 'Please enter a valid city name.', 422);
}

$email = '';
$phone = '';
$digits = preg_replace('/[\s\-()]/', '', $reach);
if (strpos($reach, '@') !== false) {
    if (!filter_var($reach, FILTER_VALIDATE_EMAIL) || !preg_match('/\.[A-Za-z]{2,}$/', $reach)) {
        octa_reply(false, 'Please enter a valid email address, e.g. name@company.com.', 422);
    }
    $email = strtolower($reach);
} elseif (preg_match('/^\+?[0-9\s\-()]+$/', $reach) && (
        preg_match('/^(\+91|91|0)?[6-9][0-9]{9}$/', $digits) ||   // Indian mobile
        preg_match('/^0[1-9][0-9]{9}$/', $digits) ||              // Indian landline with STD code
        preg_match('/^\+[1-9][0-9]{7,14}$/', $digits))) {         // International
    $phone = $reach;
} else {
    octa_reply(false, 'Please enter a valid mobile number, e.g. 98765 43210 or +91 98765 43210.', 422);
}

$message = "Enquiry received through the OCTA website chat.\n\n"
         . 'Needs help with: ' . ($need !== '' ? $need : 'Not specified') . "\n"
         . 'Sites / users: ' . ($size !== '' ? $size : 'Not specified') . "\n"
         . 'City: ' . ($city !== '' ? $city : 'Not specified');

$pageUrl = mb_substr((string) ($in['page'] ?? ''), 0, 300);

$contact = [
    'name'         => $name,
    'organization' => $company,
    'email'        => $email,
    'phone'        => $phone,
    'interest'     => $desk,
    'message'      => $message,
    'id'           => date('YmdHis') . '-' . bin2hex(random_bytes(4)),
    'source'       => 'OCTA Chatbot',
    'page_url'     => $pageUrl,
    'status'       => 'new',
    'notes'        => '',
    'created_at'   => date('c'),
];

$items = load_items(CONTACTS_FILE);
$items[] = $contact;
if (!save_contacts($items)) {
    octa_reply(false, 'Could not save your details. Please try again later.', 500);
}

// Saved for the admin panel; a mail failure is only recorded on the enquiry.
$mailCopy = $contact;
if ($mailCopy['email'] === '') $mailCopy['email'] = 'Not provided';
if ($mailCopy['phone'] === '') $mailCopy['phone'] = 'Not provided';
$sent = send_contact_email($mailCopy);
foreach ($items as $i => $it) {
    if (($it['id'] ?? '') === $contact['id']) {
        $items[$i]['email_status'] = $sent ? 'sent' : 'failed';
        $items[$i]['email_to'] = contact_desk_email($contact);
    }
}
save_contacts($items);

$recent[] = $now;
$_SESSION['octa_leads'] = array_values($recent);
octa_reply(true);
