<?php
/**
 * Business-email check + one-time-code (OTP) email verification for website forms.
 *
 * Flow: the form asks email-otp.php to send a 6-digit code to the visitor's email,
 * the visitor types it back, and the verified address is remembered in the PHP session.
 * submit_contact() then refuses any submission whose email was not verified.
 */
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/content.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('OTP_LENGTH', 6);
define('OTP_TTL', 600);               // a code is valid for 10 minutes
define('OTP_MAX_ATTEMPTS', 5);        // wrong guesses allowed per code
define('OTP_RESEND_COOLDOWN', 60);    // seconds before another code can be requested
define('OTP_VERIFIED_TTL', 3600);     // a verified address stays valid for 1 hour
define('OTP_MAX_PER_EMAIL_HOUR', 5);  // codes sent to one address per hour
define('OTP_MAX_PER_IP_HOUR', 15);    // codes requested from one IP per hour
define('OTP_RATE_FILE', __DIR__ . '/../data/otp-rate.json');

/**
 * Free / personal and disposable mailbox providers. Addresses on these domains are
 * rejected because the form only accepts business (company-domain) email.
 */
function personal_email_domains(): array {
    return [
        // Global free providers
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.in', 'yahoo.in', 'yahoo.co.uk', 'ymail.com',
        'rocketmail.com', 'outlook.com', 'outlook.in', 'hotmail.com', 'hotmail.co.uk', 'hotmail.in', 'live.com',
        'live.in', 'msn.com', 'windowslive.com', 'icloud.com', 'me.com', 'mac.com', 'aol.com', 'aim.com',
        'protonmail.com', 'proton.me', 'pm.me', 'tutanota.com', 'tuta.io', 'zoho.com', 'zohomail.com',
        'zohomail.in', 'yandex.com', 'yandex.ru', 'mail.com', 'email.com', 'gmx.com', 'gmx.net', 'gmx.de',
        'web.de', 'mail.ru', 'inbox.com', 'hushmail.com', 'fastmail.com', 'fastmail.fm', 'lycos.com',
        'qq.com', '163.com', '126.com', 'sina.com', 'naver.com', 'hanmail.net', 'rambler.ru',
        // India-specific free providers
        'rediffmail.com', 'rediff.com', 'sify.com', 'indiatimes.com', 'in.com',
        // Disposable / temporary inboxes
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com', '10minutemail.com',
        'temp-mail.org', 'tempmail.com', 'tempmail.net', 'yopmail.com', 'trashmail.com', 'getnada.com',
        'dispostable.com', 'maildrop.cc', 'throwawaymail.com', 'fakeinbox.com', 'mintemail.com',
        'emailondeck.com', 'mohmal.com', 'burnermail.io', 'moakt.com', 'tempinbox.com',
    ];
}

/** Returns an error message, or '' when $email is an acceptable business address. */
function business_email_error(string $email): string {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    $domain = strtolower(substr(strrchr($email, '@'), 1));
    if (in_array($domain, personal_email_domains(), true)) {
        return 'Please use your business email address. Personal email addresses (Gmail, Yahoo, Outlook, etc.) are not accepted.';
    }
    // The domain must be able to receive mail.
    if (function_exists('checkdnsrr') && !checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
        return 'This email domain does not appear to accept email. Please check the address.';
    }
    return '';
}

function otp_normalise_email(string $email): string {
    return strtolower(trim($email));
}

function otp_client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Records a send for this IP and email and says whether it is allowed.
 * Returns '' when allowed, otherwise the error to show.
 */
function otp_rate_check(string $email): string {
    $now = time();
    $fp = fopen(OTP_RATE_FILE, 'c+');
    if (!$fp) return '';                       // never block a real visitor over a file problem
    flock($fp, LOCK_EX);
    $data = json_decode(stream_get_contents($fp) ?: '[]', true);
    if (!is_array($data)) $data = [];

    // Forget anything older than an hour.
    foreach ($data as $k => $times) {
        // Plain closure, not fn(): the live server runs PHP 7.2 (arrow functions need 7.4).
        $data[$k] = array_values(array_filter((array) $times, function ($t) use ($now) { return $t > $now - 3600; }));
        if (!$data[$k]) unset($data[$k]);
    }

    $ipKey = 'ip:' . hash('sha256', otp_client_ip());
    $emKey = 'em:' . hash('sha256', $email);
    $error = '';
    if (count($data[$ipKey] ?? []) >= OTP_MAX_PER_IP_HOUR || count($data[$emKey] ?? []) >= OTP_MAX_PER_EMAIL_HOUR) {
        $error = 'Too many verification codes requested. Please try again in an hour.';
    } else {
        $data[$ipKey][] = $now;
        $data[$emKey][] = $now;
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $error;
}

/** Sends a new code to $email. Returns ['ok' => bool, 'message' => string]. */
function otp_send(string $email): array {
    $email = otp_normalise_email($email);
    if ($err = business_email_error($email)) {
        return ['ok' => false, 'message' => $err];
    }

    $pending = $_SESSION['email_otp'] ?? null;
    if ($pending && $pending['email'] === $email && time() - $pending['sent_at'] < OTP_RESEND_COOLDOWN) {
        $wait = OTP_RESEND_COOLDOWN - (time() - $pending['sent_at']);
        return ['ok' => false, 'message' => "Please wait $wait seconds before requesting another code.", 'wait' => $wait];
    }
    if ($err = otp_rate_check($email)) {
        return ['ok' => false, 'message' => $err];
    }

    $code = str_pad((string) random_int(0, 10 ** OTP_LENGTH - 1), OTP_LENGTH, '0', STR_PAD_LEFT);
    $minutes = intdiv(OTP_TTL, 60);

    $text = "Your Octel Networks verification code is: $code\n\n"
          . "Enter this code on the contact form to verify your email address. "
          . "It expires in $minutes minutes.\n\n"
          . "If you did not request this code, you can ignore this email.\n";
    $html = '<div style="font-family:Arial,sans-serif;font-size:15px;max-width:520px;color:#00252C">'
          . '<h2 style="margin:0 0 12px;color:#e30016">Verify your email</h2>'
          . '<p style="margin:0 0 18px">Enter this code on the Octel Networks contact form to verify your email address:</p>'
          . '<div style="font-size:32px;font-weight:700;letter-spacing:8px;padding:16px 20px;background:#f5f5f5;'
          . 'border-left:3px solid #e30016;display:inline-block">' . $code . '</div>'
          . '<p style="margin:18px 0 0;color:#777">This code expires in ' . $minutes . ' minutes.</p>'
          . '<p style="margin:8px 0 0;color:#999;font-size:13px">If you did not request this code, you can ignore this email.</p>'
          . '</div>';

    if (!send_mail($email, 'Your Octel Networks verification code: ' . $code, $html, $text)) {
        return ['ok' => false, 'message' => 'We could not send the verification email. Please try again shortly.'];
    }

    $_SESSION['email_otp'] = [
        'email'    => $email,
        'hash'     => password_hash($code, PASSWORD_DEFAULT),
        'sent_at'  => time(),
        'expires'  => time() + OTP_TTL,
        'attempts' => 0,
    ];
    return ['ok' => true, 'message' => "We've sent a $minutes-minute verification code to $email.", 'wait' => OTP_RESEND_COOLDOWN];
}

/** Checks a code the visitor typed. Returns ['ok' => bool, 'message' => string]. */
function otp_verify(string $email, string $code): array {
    $email = otp_normalise_email($email);
    $code = preg_replace('/\D/', '', $code);
    $pending = $_SESSION['email_otp'] ?? null;

    if (!$pending || $pending['email'] !== $email) {
        return ['ok' => false, 'message' => 'Please request a verification code for this email first.'];
    }
    if (time() > $pending['expires']) {
        unset($_SESSION['email_otp']);
        return ['ok' => false, 'message' => 'This code has expired. Please request a new one.'];
    }
    if ($pending['attempts'] >= OTP_MAX_ATTEMPTS) {
        unset($_SESSION['email_otp']);
        return ['ok' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
    }
    if (!password_verify($code, $pending['hash'])) {
        $_SESSION['email_otp']['attempts']++;
        $left = OTP_MAX_ATTEMPTS - $_SESSION['email_otp']['attempts'];
        return ['ok' => false, 'message' => $left > 0
            ? "Incorrect code. $left attempt" . ($left === 1 ? '' : 's') . ' left.'
            : 'Too many incorrect attempts. Please request a new code.'];
    }

    unset($_SESSION['email_otp']);
    $_SESSION['email_verified'][$email] = time();
    return ['ok' => true, 'message' => 'Email verified.'];
}

function otp_is_verified(string $email): bool {
    $t = $_SESSION['email_verified'][otp_normalise_email($email)] ?? 0;
    return $t && time() - $t < OTP_VERIFIED_TTL;
}

/** Call after a successful submission so the next one needs a fresh code. */
function otp_forget(string $email): void {
    unset($_SESSION['email_verified'][otp_normalise_email($email)]);
}
