<?php
require_once __DIR__ . '/content.php';

define('PARTNERS_FILE', __DIR__ . '/../data/partners.json');

function load_partners(): array {
    return load_items(PARTNERS_FILE);
}

function save_partners(array $partners): bool {
    return save_items(PARTNERS_FILE, $partners);
}

function get_partner(string $slug): ?array {
    return get_item(PARTNERS_FILE, $slug);
}

function get_published_partners(): array {
    return get_published_items(PARTNERS_FILE);
}

/** slug => detail page URL, for every published partner category. Falls back to partner-category-details.html?slug=... when no hand-built <slug>.html page exists. */
function partner_file_map(): array {
    $map = [];
    foreach (get_published_partners() as $p) {
        $staticFile = $p['slug'] . '.html';
        $map[$p['slug']] = file_exists(__DIR__ . '/../' . $staticFile)
            ? $staticFile
            : ('partner-category-details.html?slug=' . urlencode($p['slug']));
    }
    return $map;
}
