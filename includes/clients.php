<?php
require_once __DIR__ . '/content.php';

define('CLIENTS_FILE', __DIR__ . '/../data/clients.json');

/**
 * Client sectors shown on clients.html. Each sector is:
 *   slug, published, nav_name (section heading), short_name (filter button label),
 *   clients: [ { name, image } ]  (image is '' when no logo is available yet)
 */
function load_client_sectors(): array {
    return load_items(CLIENTS_FILE);
}

function save_client_sectors(array $sectors): bool {
    return save_items(CLIENTS_FILE, $sectors);
}

function get_client_sector(string $slug): ?array {
    return get_item(CLIENTS_FILE, $slug);
}

function get_published_client_sectors(): array {
    return get_published_items(CLIENTS_FILE);
}

/** Initials shown in the round badge when a client has no logo, e.g. "SKM Powertech" -> "SP". */
function client_initials(string $name): string {
    $base = preg_replace('/\(.*?\)/', '', $name);
    $stop = ['of', 'the', 'and', 'for', '&', 'its', 'india'];
    $words = array_values(array_filter(preg_split('/[\s\-]+/', $base), function ($w) use ($stop) {
        return $w !== '' && !in_array(strtolower($w), $stop, true);
    }));
    if (!$words) return '?';
    if (count($words) === 1) {
        $w = $words[0];
        return strtoupper($w) === $w ? substr($w, 0, 3) : strtoupper(substr($w, 0, 2));
    }
    return strtoupper($words[0][0] . $words[1][0]);
}
