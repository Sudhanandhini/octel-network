<?php
/**
 * Shared admin list search.
 *   admin_search_query()                      - the trimmed ?q= value
 *   admin_search_filter($items, $q, $fields)  - keep items where any field contains $q (case-insensitive)
 *   render_admin_search($q, $hidden, $placeholder) - the search box; $hidden keeps other filters (e.g. status) in the URL
 */

function admin_search_query(): string {
    return trim((string)($_GET['q'] ?? ''));
}

function admin_search_filter(array $items, string $q, array $fields): array {
    if ($q === '') return $items;
    $needle = mb_strtolower($q);
    return array_values(array_filter($items, function ($item) use ($needle, $fields) {
        foreach ($fields as $f) {
            $value = $item[$f] ?? '';
            if (is_array($value)) $value = implode(' ', $value);
            if (mb_strpos(mb_strtolower((string)$value), $needle) !== false) return true;
        }
        return false;
    }));
}

function render_admin_search(string $q, array $hidden = [], string $placeholder = 'Search…'): void {
    ?>
    <form method="get" class="admin-search" role="search">
        <?php foreach ($hidden as $name => $value): if ($value === '' || $value === null) continue; ?>
            <input type="hidden" name="<?= e($name) ?>" value="<?= e((string)$value) ?>">
        <?php endforeach; ?>
        <i class="ri-search-line"></i>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e($placeholder) ?>" aria-label="Search">
        <button type="submit" class="btn btn-sm">Search</button>
        <?php if ($q !== ''): ?>
            <a href="?<?= e(http_build_query(array_filter($hidden, 'strlen'))) ?>" class="btn btn-sm btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
    <?php
}
