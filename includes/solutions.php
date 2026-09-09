<?php
require_once __DIR__ . '/content.php';

define('SOLUTIONS_FILE', __DIR__ . '/../data/solutions.json');

function load_solutions(): array {
    return load_items(SOLUTIONS_FILE);
}

function save_solutions(array $solutions): bool {
    return save_items(SOLUTIONS_FILE, $solutions);
}

function get_solution(string $slug): ?array {
    return get_item(SOLUTIONS_FILE, $slug);
}

function get_published_solutions(): array {
    return get_published_items(SOLUTIONS_FILE);
}

/** slug => detail page URL, for every published solution. Falls back to solution-details.html?slug=... when no hand-built <slug>.html page exists. */
function solution_file_map(): array {
    $map = [];
    foreach (get_published_solutions() as $s) {
        $staticFile = $s['slug'] . '.html';
        $map[$s['slug']] = file_exists(__DIR__ . '/../' . $staticFile)
            ? $staticFile
            : ('solution-details.html?slug=' . urlencode($s['slug']));
    }
    return $map;
}
