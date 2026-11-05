<?php
require_once __DIR__ . '/content.php';

define('LEADERS_FILE', __DIR__ . '/../data/leaders.json');

function load_leaders(): array {
    return load_items(LEADERS_FILE);
}

function get_leader(string $slug): ?array {
    return get_item(LEADERS_FILE, $slug);
}

function get_published_leaders(): array {
    return get_published_items(LEADERS_FILE);
}

function leader_url(array $leader): string {
    return 'leader-details.html?slug=' . urlencode($leader['slug']);
}
