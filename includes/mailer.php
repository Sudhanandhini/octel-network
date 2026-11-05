<?php
require_once __DIR__ . '/env.php';

/**
 * Send a plain-text + HTML email using the SMTP settings in .env.
 * Falls back to PHP mail() when SMTP_HOST is empty.
 * Returns true on success; failures are written to the PHP error log.
 */
function send_mail(string $to, string $subject, string $html, string $text, string $replyTo = ''): bool {
    $fromEmail = env('MAIL_FROM', env('SMTP_USER'));
    $fromName  = env('MAIL_FROM_NAME', 'Octel Networks Website');
    if ($to === '' || $fromEmail === '') {
        error_log('send_mail: recipient or MAIL_FROM is not configured in .env');
        return false;
    }

    $boundary = 'b' . bin2hex(random_bytes(8));
    $headers = [
        'From: ' . mail_encode_header($fromName) . " <$fromEmail>",
        'MIME-Version: 1.0',
        "Content-Type: multipart/alternative; boundary=\"$boundary\"",
    ];
    if ($replyTo !== '') $headers[] = "Reply-To: $replyTo";

    $body = "--$boundary\r\n"
          . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($text)) . "\r\n"
          . "--$boundary\r\n"
          . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($html)) . "\r\n"
          . "--$boundary--\r\n";

    if (env('SMTP_HOST') === '') {
        $ok = mail($to, mail_encode_header($subject), $body, implode("\r\n", $headers));
        if (!$ok) error_log("send_mail: mail() failed for $to");
        return $ok;
    }

    $headers = array_merge([
        "To: $to",
        'Subject: ' . mail_encode_header($subject),
        'Date: ' . date('r'),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (explode('@', $fromEmail)[1] ?? 'localhost') . '>',
    ], $headers);

    try {
        smtp_send($fromEmail, $to, implode("\r\n", $headers) . "\r\n\r\n" . $body);
        return true;
    } catch (RuntimeException $e) {
        error_log('send_mail: ' . $e->getMessage());
        return false;
    }
}

function mail_encode_header(string $value): string {
    return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
}

/** Talk SMTP directly: SMTP_SECURE = ssl (port 465), tls (STARTTLS, port 587) or none. */
function smtp_send(string $from, string $to, string $data): void {
    $host   = env('SMTP_HOST');
    $port   = (int) env('SMTP_PORT', '587');
    $secure = strtolower(env('SMTP_SECURE', 'tls'));

    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port";
    $fp = @stream_socket_client($remote, $errno, $errstr, 15);
    if (!$fp) throw new RuntimeException("SMTP connect to $remote failed: $errstr");
    stream_set_timeout($fp, 15);

    $read = function (int $expect) use ($fp): string {
        $reply = '';
        while (($line = fgets($fp, 515)) !== false) {
            $reply .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        if ((int) substr($reply, 0, 3) !== $expect) {
            throw new RuntimeException('SMTP error: ' . trim($reply));
        }
        return $reply;
    };
    $cmd = function (string $line, int $expect) use ($fp, $read): string {
        fwrite($fp, $line . "\r\n");
        return $read($expect);
    };

    try {
        $read(220);
        $ehloHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $cmd("EHLO $ehloHost", 250);
        if ($secure === 'tls') {
            $cmd('STARTTLS', 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP STARTTLS failed');
            }
            $cmd("EHLO $ehloHost", 250);
        }
        if (env('SMTP_USER') !== '') {
            $cmd('AUTH LOGIN', 334);
            $cmd(base64_encode(env('SMTP_USER')), 334);
            $cmd(base64_encode(env('SMTP_PASS')), 235);
        }
        $cmd("MAIL FROM:<$from>", 250);
        $cmd("RCPT TO:<$to>", 250);
        $cmd('DATA', 354);
        // Dot-stuff lines that start with "." and terminate the message.
        $cmd(preg_replace('/^\./m', '..', $data) . "\r\n.", 250);
        $cmd('QUIT', 221);
    } finally {
        fclose($fp);
    }
}
