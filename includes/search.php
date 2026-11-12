<?php
/**
 * Site search for the header search box (search.html?q=...).
 * Builds an in-memory index from the JSON content collections and the main
 * static pages, then ranks pages that contain every search word.
 */
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/solutions.php';
require_once __DIR__ . '/distributors.php';
require_once __DIR__ . '/partners.php';
require_once __DIR__ . '/leaders.php';
require_once __DIR__ . '/events.php';
require_once __DIR__ . '/careers.php';

/** Main pages whose visible text is searchable, with the title shown in results. */
function search_static_pages(): array {
    return [
        'index.html' => 'Home',
        'about.html' => 'About Us',
        'why-octel.html' => 'Why Octel',
        'services.html' => 'Solutions',
        'distribution.html' => 'Distribution',
        'partners.html' => 'Our Partners',
        'our-leaders.html' => 'Our Leaders',
        'clients.html' => 'Our Clients',
        'case-studies.html' => 'Case Studies',
        'careers.html' => 'Careers',
        'contact.html' => 'Contact Us',
    ];
}

/** Fields that identify an item rather than describe it; matched via the title, kept out of snippets. */
function search_name_fields(): array {
    return ['slug', 'published', 'nav_name', 'page_title', 'name', 'title', 'role', 'created_at'];
}

/** Collapse a value (string or nested array from JSON) into plain text. */
function search_flatten($value): string {
    if (is_array($value)) {
        $value = array_diff_key($value, array_flip(search_name_fields()));
        return implode(' ', array_map('search_flatten', $value));
    }
    if (!is_string($value) || preg_match('#^(assets/|https?://)#', $value)) return '';
    return $value;
}

function search_clean(string $text): string {
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text));
}

/** Visible body text of a static page: between the header and the footer, without PHP or scripts. */
function search_page_text(string $file): string {
    $path = __DIR__ . '/../' . $file;
    if (!is_file($path)) return '';
    $html = file_get_contents($path);
    $start = strpos($html, '<!-- Navbar Area End -->');
    $end = strpos($html, '<!-- Footer Section Start -->');
    if ($start !== false && $end !== false && $end > $start) {
        $html = substr($html, $start, $end - $start);
    }
    $html = preg_replace('#<\?php.*?\?>|<\?=.*?\?>|<script\b.*?</script>|<style\b.*?</style>|<!--.*?-->#s', ' ', $html);
    return search_clean($html);
}

function search_documents(): array {
    $docs = [];
    // $names: other names for the item (e.g. its menu name) that should match like the title does.
    $add = function (string $title, string $url, string $type, string $text, string $names = '') use (&$docs) {
        $docs[] = ['title' => search_clean($title), 'names' => search_clean($names), 'url' => $url, 'type' => $type, 'text' => search_clean($text)];
    };

    $collections = [
        ['Solution', get_published_solutions(), solution_file_map()],
        ['Distributor', get_published_distributors(), distributor_file_map()],
        ['Partner', get_published_partners(), partner_file_map()],
    ];
    foreach ($collections as [$type, $items, $map]) {
        foreach ($items as $item) {
            if (empty($map[$item['slug']])) continue;
            $add($item['page_title'] ?: $item['nav_name'], $map[$item['slug']], $type, search_flatten($item), $item['nav_name'] ?? '');
        }
    }
    foreach (get_published_leaders() as $l) {
        $add($l['name'] . ' – ' . $l['role'], leader_url($l), 'Leader', search_flatten($l));
    }
    foreach (get_published_events() as $ev) {
        $add($ev['title'], event_url($ev), 'Event', search_flatten($ev));
    }
    foreach (get_published_jobs() as $job) {
        $add($job['title'], 'career-details.html?slug=' . urlencode($job['slug']), 'Job Opening', search_flatten($job));
    }
    foreach (search_static_pages() as $file => $title) {
        $add($title, $file, 'Page', search_page_text($file));
    }
    return $docs;
}

/** Search words: lowercase, at least 2 characters, at most 8 of them. */
function search_terms(string $query): array {
    $words = preg_split('/\s+/u', mb_strtolower(trim($query)), -1, PREG_SPLIT_NO_EMPTY);
    $words = array_filter($words, function ($w) { return mb_strlen($w) >= 2; });
    return array_slice(array_values(array_unique($words)), 0, 8);
}

/** Returns matching documents, best first, each with a short 'snippet' around the first match. */
function search_run(string $query): array {
    $terms = search_terms($query);
    if (!$terms) return [];
    $results = [];
    foreach (search_documents() as $doc) {
        $title = mb_strtolower($doc['title'] . ' ' . $doc['names']);
        $text = mb_strtolower($doc['text']);
        $score = 0;
        foreach ($terms as $t) {
            $inTitle = mb_substr_count($title, $t);
            $inText = mb_substr_count($text, $t);
            if (!$inTitle && !$inText) continue 2;   // every word must appear
            $score += $inTitle * 10 + min($inText, 20);
        }
        $doc['score'] = $score;
        $doc['snippet'] = search_snippet($doc['text'], $terms);
        $results[] = $doc;
    }
    usort($results, function ($a, $b) { return $b['score'] <=> $a['score']; });
    return $results;
}

function search_snippet(string $text, array $terms, int $length = 220): string {
    $lower = mb_strtolower($text);
    $pos = false;
    foreach ($terms as $t) {
        $p = mb_strpos($lower, $t);
        if ($p !== false && ($pos === false || $p < $pos)) $pos = $p;
    }
    $start = max(0, (int)$pos - 60);
    $snippet = mb_substr($text, $start, $length);
    return ($start > 0 ? '… ' : '') . trim($snippet) . (mb_strlen($text) > $start + $length ? ' …' : '');
}

/** Escape a snippet for HTML and wrap each search word in <mark>. */
function search_highlight(string $snippet, array $terms): string {
    $html = e($snippet);
    if (!$terms) return $html;
    $pattern = '/(' . implode('|', array_map(function ($t) { return preg_quote(e($t), '/'); }, $terms)) . ')/iu';
    return preg_replace($pattern, '<mark>$1</mark>', $html);
}
