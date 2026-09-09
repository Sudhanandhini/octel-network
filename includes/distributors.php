<?php
require_once __DIR__ . '/content.php';

define('DISTRIBUTORS_FILE', __DIR__ . '/../data/distributors.json');

function load_distributors(): array {
    return load_items(DISTRIBUTORS_FILE);
}

function save_distributors(array $distributors): bool {
    return save_items(DISTRIBUTORS_FILE, $distributors);
}

function get_distributor(string $slug): ?array {
    return get_item(DISTRIBUTORS_FILE, $slug);
}

function get_published_distributors(): array {
    return get_published_items(DISTRIBUTORS_FILE);
}

/** Slugs with a hand-built detail page. Anything else falls back to partner-details.html?slug=... */
function distributor_static_files(): array {
    return [
        'rittal' => 'rittal-details.html',
        'rm' => 'rm-details.html',
        'netka' => 'netka-details.html',
        'matrix' => 'matrix-details.html',
    ];
}

/** slug => detail page URL, for every published distributor. */
function distributor_file_map(): array {
    $static = distributor_static_files();
    $map = [];
    foreach (get_published_distributors() as $d) {
        $map[$d['slug']] = $static[$d['slug']] ?? ('partner-details.html?slug=' . urlencode($d['slug']));
    }
    return $map;
}
