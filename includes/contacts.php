<?php
require_once __DIR__ . '/content.php';

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
 * Returns ['ok' => true] or ['ok' => false, 'errors' => [...], 'old' => [...]].
 */
function submit_contact(array $post, string $source, string $pageUrl = ''): array {
    $old = [
        'name'     => trim($post['name'] ?? ''),
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
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone'])) $errors[] = 'Please enter a valid phone number.';
    if (!in_array($old['interest'], ['', 'Sales', 'Support'], true)) $old['interest'] = '';
    if (mb_strlen($old['message']) > 5000) $errors[] = 'Message is too long (5000 characters max).';

    if ($errors) return ['ok' => false, 'errors' => $errors, 'old' => $old];

    $items = load_items(CONTACTS_FILE);
    $items[] = $old + [
        'id'         => date('YmdHis') . '-' . bin2hex(random_bytes(4)),
        'source'     => $source,
        'page_url'   => $pageUrl,
        'status'     => 'new',
        'notes'      => '',
        'created_at' => date('c'),
    ];
    if (!save_contacts($items)) {
        return ['ok' => false, 'errors' => ['Could not send your message. Please try again later.'], 'old' => $old];
    }
    return ['ok' => true];
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
