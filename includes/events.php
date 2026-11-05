<?php
require_once __DIR__ . '/content.php';

define('EVENTS_FILE', __DIR__ . '/../data/events.json');

function load_events(): array {
    return load_items(EVENTS_FILE);
}

function get_event(string $slug): ?array {
    return get_item(EVENTS_FILE, $slug);
}

function get_published_events(): array {
    return get_published_items(EVENTS_FILE);
}

function event_url(array $event): string {
    return 'event-details.html?slug=' . urlencode($event['slug']);
}
