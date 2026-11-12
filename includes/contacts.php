<?php
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/email-verify.php';

date_default_timezone_set('Asia/Kolkata');

define('CONTACTS_FILE', __DIR__ . '/../data/contacts.json');

function load_contacts(): array {
    $items = load_items(CONTACTS_FILE);
    usort($items, function ($a, $b) {
        return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
    });
    return $items;
}

function save_contacts(array $items): bool {
    return save_items(CONTACTS_FILE, $items);
}

function get_contact(string $id): ?array {
    foreach (load_items(CONTACTS_FILE) as $c) {
        if (($c['id'] ?? '') === $id) return $c;
    }
    return null;
}

/**
 * Country dialling codes for the phone field: ISO code => [country, dial code].
 * The first entries are pinned to the top of the dropdown; the rest are alphabetical.
 */
function phone_country_codes(): array {
    return [
        'IN' => ['India', '+91'],
        'AE' => ['United Arab Emirates', '+971'],
        'US' => ['United States', '+1'],
        'GB' => ['United Kingdom', '+44'],
        'SG' => ['Singapore', '+65'],
        'SA' => ['Saudi Arabia', '+966'],
        'QA' => ['Qatar', '+974'],
        'OM' => ['Oman', '+968'],
        'KW' => ['Kuwait', '+965'],
        'BH' => ['Bahrain', '+973'],
        'AF' => ['Afghanistan', '+93'],
        'AR' => ['Argentina', '+54'],
        'AU' => ['Australia', '+61'],
        'AT' => ['Austria', '+43'],
        'BD' => ['Bangladesh', '+880'],
        'BE' => ['Belgium', '+32'],
        'BT' => ['Bhutan', '+975'],
        'BR' => ['Brazil', '+55'],
        'CA' => ['Canada', '+1'],
        'CL' => ['Chile', '+56'],
        'CN' => ['China', '+86'],
        'CO' => ['Colombia', '+57'],
        'CZ' => ['Czech Republic', '+420'],
        'DK' => ['Denmark', '+45'],
        'EG' => ['Egypt', '+20'],
        'FI' => ['Finland', '+358'],
        'FR' => ['France', '+33'],
        'DE' => ['Germany', '+49'],
        'GR' => ['Greece', '+30'],
        'HK' => ['Hong Kong', '+852'],
        'HU' => ['Hungary', '+36'],
        'ID' => ['Indonesia', '+62'],
        'IR' => ['Iran', '+98'],
        'IQ' => ['Iraq', '+964'],
        'IE' => ['Ireland', '+353'],
        'IL' => ['Israel', '+972'],
        'IT' => ['Italy', '+39'],
        'JP' => ['Japan', '+81'],
        'JO' => ['Jordan', '+962'],
        'KE' => ['Kenya', '+254'],
        'LB' => ['Lebanon', '+961'],
        'MY' => ['Malaysia', '+60'],
        'MV' => ['Maldives', '+960'],
        'MU' => ['Mauritius', '+230'],
        'MX' => ['Mexico', '+52'],
        'MA' => ['Morocco', '+212'],
        'MM' => ['Myanmar', '+95'],
        'NP' => ['Nepal', '+977'],
        'NL' => ['Netherlands', '+31'],
        'NZ' => ['New Zealand', '+64'],
        'NG' => ['Nigeria', '+234'],
        'NO' => ['Norway', '+47'],
        'PK' => ['Pakistan', '+92'],
        'PH' => ['Philippines', '+63'],
        'PL' => ['Poland', '+48'],
        'PT' => ['Portugal', '+351'],
        'RO' => ['Romania', '+40'],
        'RU' => ['Russia', '+7'],
        'ZA' => ['South Africa', '+27'],
        'KR' => ['South Korea', '+82'],
        'ES' => ['Spain', '+34'],
        'LK' => ['Sri Lanka', '+94'],
        'SE' => ['Sweden', '+46'],
        'CH' => ['Switzerland', '+41'],
        'TW' => ['Taiwan', '+886'],
        'TZ' => ['Tanzania', '+255'],
        'TH' => ['Thailand', '+66'],
        'TR' => ['Turkey', '+90'],
        'UG' => ['Uganda', '+256'],
        'UA' => ['Ukraine', '+380'],
        'VN' => ['Vietnam', '+84'],
        'YE' => ['Yemen', '+967'],
        'ZM' => ['Zambia', '+260'],
        'ZW' => ['Zimbabwe', '+263'],
    ];
}

/** status key => label */
function contact_statuses(): array {
    return [
        'new'       => 'New',
        'contacted' => 'Contacted',
        'closed'    => 'Closed',
    ];
}

function count_contacts_by_status(array $items): array {
    $counts = array_fill_keys(array_keys(contact_statuses()), 0);
    foreach ($items as $c) {
        $s = $c['status'] ?? 'new';
        if (isset($counts[$s])) $counts[$s]++;
    }
    return $counts;
}

/**
 * Validate and store a contact form submission.
 * $source describes where it came from, e.g. "Contact Page" or "Solution: Cybersecurity".
 * $options:
 *   'verify_email'         => true  requires a business email verified with a one-time code
 *                                   (see includes/email-verify.php and email-otp.php)
 *   'require_organization' => true  makes the Organization Name field mandatory
 *   'require_all'          => true  also makes Service Interest and Message mandatory
 * Returns ['ok' => true] or ['ok' => false, 'errors' => [...], 'old' => [...]].
 */
function submit_contact(array $post, string $source, string $pageUrl = '', array $options = []): array {
    $verifyEmail = !empty($options['verify_email']);
    $requireOrg  = !empty($options['require_organization']);
    $requireAll  = !empty($options['require_all']);
    $old = [
        'name'     => trim($post['name'] ?? ''),
        'organization' => trim($post['organization'] ?? ''),
        'email'    => trim($post['email'] ?? ''),
        'phone'    => trim($post['phone'] ?? ''),
        'interest' => trim($post['interest'] ?? ''),
        'message'  => trim($post['message'] ?? ''),
    ];

    // Honeypot: real users never fill this hidden field.
    if (trim($post['website'] ?? '') !== '') {
        return ['ok' => true];
    }

    $errors = [];
    if ($old['name'] === '' || mb_strlen($old['name']) > 120) $errors[] = 'Please enter your full name.';
    if (($requireOrg && $old['organization'] === '') || mb_strlen($old['organization']) > 150) {
        $errors[] = 'Please enter your organization name.';
    }
    if ($verifyEmail) {
        if ($err = business_email_error($old['email'])) {
            $errors[] = $err;
        } elseif (!otp_is_verified($old['email'])) {
            $errors[] = 'Please verify your email address with the code we send you before submitting.';
        }
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (array_key_exists('phone_country', $post)) {
        // Phone sent as country code + local number (contact page): store "+91 98765 43210".
        $codes = phone_country_codes();
        $country = strtoupper(trim((string) $post['phone_country']));
        $old['phone_country'] = isset($codes[$country]) ? $country : 'IN';
        $local = preg_replace('/[\s\-().]/', '', $old['phone']);
        $local = ltrim($local, '0');           // drop a trunk "0" typed before the number
        if (!isset($codes[$country])) {
            $errors[] = 'Please choose a country code.';
        } elseif (!preg_match('/^[0-9]{6,14}$/', $local)) {
            $errors[] = 'Please enter a valid mobile number (digits only, without the country code).';
        } else {
            $old['phone_full'] = $codes[$country][1] . ' ' . $local;
        }
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!in_array($old['interest'], ['', 'Sales', 'Support'], true)) $old['interest'] = '';
    if ($requireAll && $old['interest'] === '') $errors[] = 'Please choose Sales or Support.';
    if ($requireAll && $old['message'] === '') {
        $errors[] = 'Please tell us how we can help.';
    } elseif (mb_strlen($old['message']) > 5000) {
        $errors[] = 'Message is too long (5000 characters max).';
    }

    if ($errors) return ['ok' => false, 'errors' => $errors, 'old' => $old];

    $items = load_items(CONTACTS_FILE);
    $record = $old;
    if (isset($record['phone_full'])) $record['phone'] = $record['phone_full'];
    unset($record['phone_full'], $record['phone_country']);
    $contact = $record + [
        'id'         => date('YmdHis') . '-' . bin2hex(random_bytes(4)),
        'source'     => $source,
        'page_url'   => $pageUrl,
        'status'     => 'new',
        'notes'      => '',
        'created_at' => date('c'),
    ];
    $items[] = $contact;
    if (!save_contacts($items)) {
        return ['ok' => false, 'errors' => ['Could not send your message. Please try again later.'], 'old' => $old];
    }
    // The enquiry is already saved for the admin panel, so a mail failure is only logged
    // and recorded on the enquiry, where the admin Contacts page shows it.
    $sent = send_contact_email($contact);
    foreach ($items as $i => $it) {
        if (($it['id'] ?? '') === $contact['id']) {
            $items[$i]['email_status'] = $sent ? 'sent' : 'failed';
            $items[$i]['email_to'] = contact_desk_email($contact);
        }
    }
    save_contacts($items);
    if ($verifyEmail) otp_forget($old['email']);
    return ['ok' => true];
}

/** The desk address from .env that an enquiry is emailed to: Support or Sales. */
function contact_desk_email(array $c): string {
    return ($c['interest'] ?? '') === 'Support' ? env('SUPPORT_EMAIL') : env('SALES_EMAIL');
}

/** Email a new enquiry to the Sales or Support desk address set in .env. */
function send_contact_email(array $c): bool {
    $isSupport = ($c['interest'] ?? '') === 'Support';
    $to = contact_desk_email($c);
    $desk = $isSupport ? 'Support' : 'Sales';

    $fields = [
        'Name'     => $c['name'],
        'Organization' => ($c['organization'] ?? '') !== '' ? $c['organization'] : 'Not provided',
        'Email'    => $c['email'],
        'Phone'    => $c['phone'],
        'Interest' => $c['interest'] !== '' ? $c['interest'] : 'Not specified',
        'Source'   => $c['source'],
        'Page'     => $c['page_url'],
        'Received' => format_contact_date($c['created_at']),
    ];

    $text = "New $desk enquiry from the Octel Networks website\n\n";
    $rows = '';
    foreach ($fields as $label => $value) {
        $text .= "$label: $value\n";
        $rows .= '<tr><td style="padding:6px 14px 6px 0;color:#777;white-space:nowrap;vertical-align:top">' . $label
               . '</td><td style="padding:6px 0;color:#00252C;font-weight:600">' . e($value) . '</td></tr>';
    }
    $text .= "\nMessage:\n" . ($c['message'] !== '' ? $c['message'] : '(no message)') . "\n";

    $html = '<div style="font-family:Arial,sans-serif;font-size:15px;max-width:600px">'
          . '<h2 style="margin:0 0 4px;color:#e30016">New ' . $desk . ' Enquiry</h2>'
          . '<p style="margin:0 0 18px;color:#777">Submitted via the Octel Networks website</p>'
          . '<table style="border-collapse:collapse;margin-bottom:18px">' . $rows . '</table>'
          . '<p style="margin:0 0 6px;color:#777">Message</p>'
          . '<div style="padding:14px;background:#f5f5f5;border-left:3px solid #e30016;white-space:pre-wrap">'
          . e($c['message'] !== '' ? $c['message'] : '(no message)') . '</div>'
          . '<p style="margin-top:18px;color:#999;font-size:13px">Reply to this email to respond to ' . e($c['name']) . ' directly.</p>'
          . '</div>';

    $subject = "New $desk Enquiry – " . $c['name'];
    // Name is already validated; strip line breaks so it can't inject headers.
    $replyTo = str_replace(["\r", "\n"], '', $c['email']);
    return send_mail($to, $subject, $html, $text, $replyTo);
}

function delete_contact(string $id): bool {
    $items = array_values(array_filter(load_items(CONTACTS_FILE), function ($c) use ($id) {
        return ($c['id'] ?? '') !== $id;
    }));
    return save_contacts($items);
}

/** Current request path + query with `sent` / anchor stripped, for redirect-after-POST. */
function contact_redirect_url(string $anchor): string {
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
    unset($query['sent']);
    $query['sent'] = 1;
    return $path . '?' . http_build_query($query) . '#' . $anchor;
}

function format_contact_date(?string $iso): string {
    if (!$iso) return '';
    $ts = strtotime($iso);
    return $ts ? date('j F Y \a\t h:i a', $ts) : $iso;
}
