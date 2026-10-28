<?php
/**
 * Generic JSON-backed content collection engine.
 * Used by includes/distributors.php and includes/solutions.php, which each
 * bind these functions to their own data file and expose collection-specific
 * function names (get_distributor(), get_solution(), etc.) so callers don't
 * need to know this generic layer exists.
 */

function load_items(string $file): array {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_items(string $file, array $items): bool {
    $fp = fopen($file, 'c+');
    if (!$fp) return false;
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    ftruncate($fp, 0);
    rewind($fp);
    $ok = fwrite($fp, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !== false;
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $ok;
}

function get_item(string $file, string $slug): ?array {
    foreach (load_items($file) as $item) {
        if (($item['slug'] ?? '') === $slug) return $item;
    }
    return null;
}

function get_published_items(string $file): array {
    return array_values(array_filter(load_items($file), function ($item) {
        return !empty($item['published']);
    }));
}

/** Split text into trimmed, non-empty lines. */
function parse_lines(string $text): array {
    $lines = preg_split('/\r\n|\r|\n/', trim($text));
    return array_values(array_filter(array_map('trim', $lines), function ($l) {
        return $l !== '';
    }));
}

/** Split text into paragraphs on blank lines. */
function parse_paragraphs(string $text): array {
    $blocks = preg_split('/(?:\r\n|\r|\n){2,}/', trim($text));
    return array_values(array_filter(array_map('trim', $blocks), function ($p) {
        return $p !== '';
    }));
}

/** Parse "a | b | c" lines into associative rows keyed by $keys. */
function parse_pipe_rows(string $text, array $keys): array {
    $rows = [];
    foreach (parse_lines($text) as $line) {
        $parts = array_map('trim', explode('|', $line));
        $row = [];
        foreach ($keys as $i => $key) {
            $row[$key] = $parts[$i] ?? '';
        }
        $rows[] = $row;
    }
    return $rows;
}

/** Reverse of parse_pipe_rows(), for pre-filling edit forms. */
function rows_to_pipe_text(array $rows, array $keys): string {
    $lines = [];
    foreach ($rows as $row) {
        $lines[] = implode(' | ', array_map(function ($k) use ($row) {
            return $row[$k] ?? '';
        }, $keys));
    }
    return implode("\n", $lines);
}

/** Turn a name into a URL-safe slug, e.g. "R&M (Reichle)" -> "r-m-reichle". */
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

/** Append -2, -3, ... to $baseSlug until it doesn't collide with $existingSlugs. */
function unique_slug(string $baseSlug, array $existingSlugs): string {
    $slug = $baseSlug;
    $n = 2;
    while (in_array($slug, $existingSlugs, true)) {
        $slug = $baseSlug . '-' . $n;
        $n++;
    }
    return $slug;
}

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Render the free-form "page builder" blocks saved by the admin block editor
 * (see admin/includes/block-editor.php). Supports: heading, paragraph, image,
 * button, table, and row (with nested columns of the block types above).
 */
function render_content_blocks(array $blocks): void {
    foreach ($blocks as $block) {
        if (!is_array($block)) continue;
        $type = $block['type'] ?? '';
        switch ($type) {
            case 'heading':
                $level = in_array($block['level'] ?? '', ['h2', 'h3', 'h4'], true) ? $block['level'] : 'h2';
                echo "<{$level} class=\"custom-block-heading\">" . e($block['text'] ?? '') . "</{$level}>";
                break;

            case 'paragraph':
                if (trim($block['text'] ?? '') !== '') {
                    echo '<p class="custom-block-paragraph">' . nl2br(e($block['text'])) . '</p>';
                }
                break;

            case 'image':
                if (!empty($block['src'])) {
                    echo '<figure class="custom-block-image">';
                    echo '<img src="' . e($block['src']) . '" alt="' . e($block['alt'] ?? '') . '" class="w-100 round-20">';
                    if (!empty($block['caption'])) {
                        echo '<figcaption>' . e($block['caption']) . '</figcaption>';
                    }
                    echo '</figure>';
                }
                break;

            case 'button':
                if (trim($block['text'] ?? '') !== '') {
                    echo '<a href="' . e($block['href'] ?? '#') . '" class="custom-block-btn">' . e($block['text']) . '</a>';
                }
                break;

            case 'table':
                $rows = array_map(function ($line) {
                    return array_map('trim', explode('|', $line));
                }, parse_lines($block['text'] ?? ''));
                if (!empty($rows)) {
                    echo '<div class="table-responsive custom-block-table"><table class="table">';
                    if (!empty($block['header'])) {
                        $headRow = array_shift($rows);
                        echo '<thead><tr>';
                        foreach ($headRow as $cell) {
                            echo '<th>' . e($cell) . '</th>';
                        }
                        echo '</tr></thead>';
                    }
                    echo '<tbody>';
                    foreach ($rows as $row) {
                        echo '<tr>';
                        foreach ($row as $cell) {
                            echo '<td>' . e($cell) . '</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</tbody></table></div>';
                }
                break;

            case 'row':
                $columns = is_array($block['columns'] ?? null) ? $block['columns'] : [];
                $count = max(1, count($columns));
                $colClass = 'col-md-' . max(1, (int) floor(12 / $count));
                echo '<div class="row custom-block-row">';
                foreach ($columns as $colBlocks) {
                    echo '<div class="' . $colClass . ' mb-20">';
                    render_content_blocks(is_array($colBlocks) ? $colBlocks : []);
                    echo '</div>';
                }
                echo '</div>';
                break;
        }
    }
}

/** Escape a product-range line, then apply its inline markup: **bold** and [[source tag]]. */
function product_range_inline(string $text): string {
    $html = e($text);
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    return preg_replace('/\[\[(.+?)\]\]/', '<span class="dp-src">$1</span>', $html);
}

/**
 * Render the "Product range" text field as heading groups and cards.
 *   ## 1. Group heading           (a leading "N." becomes a number badge)
 *   ### Card title                or: ### ri-camera-line | Card title
 *   - List item                   inside the current card
 *   Plain line                    paragraph inside the current card
 *   ! Highlighted line            bold boxed line inside the current card
 *   > Line                        paragraph outside the cards (lead or note)
 *   ---                           start a new row of cards
 * Inline: **bold** and [[source tag]].
 * Cards with paragraphs span the full width; a row of exactly three list cards
 * sits three across; otherwise two across, with an odd last card full width.
 */
function render_product_range(string $text): void {
    $blocks = [];
    $bi = null; // index of the open card's row in $blocks
    $ci = null; // index of the open card in that row
    foreach (parse_lines($text) as $line) {
        if (strpos($line, '### ') === 0) {
            $title = trim(substr($line, 4));
            $icon = '';
            $parts = array_map('trim', explode('|', $title, 2));
            if (count($parts) === 2 && strpos($parts[0], 'ri-') === 0) {
                [$icon, $title] = $parts;
            }
            // $blocks and each row's cards are plain lists, so the last index is count - 1
            // (array_key_last() would need PHP 7.3; the live server runs 7.2).
            $lastIndex = count($blocks) - 1;
            if ($lastIndex < 0 || $blocks[$lastIndex]['type'] !== 'cards') {
                $blocks[] = ['type' => 'cards', 'cards' => []];
                $lastIndex = count($blocks) - 1;
            }
            $blocks[$lastIndex]['cards'][] = ['icon' => $icon, 'title' => $title, 'body' => []];
            $bi = $lastIndex;
            $ci = count($blocks[$lastIndex]['cards']) - 1;
            continue;
        }
        if (strpos($line, '## ') === 0) {
            $bi = $ci = null;
            $blocks[] = ['type' => 'group', 'text' => trim(substr($line, 3))];
            continue;
        }
        if ($line === '---') {
            $bi = $ci = null;
            $blocks[] = ['type' => 'break'];
            continue;
        }
        if ($line[0] === '>' || $bi === null) {
            $bi = $ci = null;
            $blocks[] = ['type' => 'para', 'text' => $line[0] === '>' ? trim(substr($line, 1)) : $line];
            continue;
        }
        if (strpos($line, '- ') === 0) {
            $entry = ['type' => 'item', 'text' => trim(substr($line, 2))];
        } elseif (strpos($line, '! ') === 0) {
            $entry = ['type' => 'highlight', 'text' => trim(substr($line, 2))];
        } else {
            $entry = ['type' => 'para', 'text' => $line];
        }
        $blocks[$bi]['cards'][$ci]['body'][] = $entry;
    }

    if (empty($blocks)) return;

    $numbered = function (string $title): string {
        if (preg_match('/^(\d+)\.\s+(.*)$/', $title, $m)) {
            return '<span class="dp-num">' . e($m[1]) . '</span>' . product_range_inline($m[2]);
        }
        return product_range_inline($title);
    };

    $seenCards = false;
    echo '<div class="dp-section">';
    foreach ($blocks as $block) {
        if ($block['type'] === 'group') {
            echo '<h4 class="dp-group">' . $numbered($block['text']) . '</h4>';
        } elseif ($block['type'] === 'para') {
            echo '<p class="' . ($seenCards ? 'dp-note' : 'dp-lead') . '">' . product_range_inline($block['text']) . '</p>';
        } elseif ($block['type'] === 'cards') {
            $seenCards = true;
            $cards = $block['cards'];
            $count = count($cards);
            echo '<div class="row">';
            foreach ($cards as $i => $c) {
                $hasPara = false;
                foreach ($c['body'] as $b) {
                    if ($b['type'] !== 'item') $hasPara = true;
                }
                if ($hasPara) {
                    $col = 'col-12';
                } elseif ($count === 3) {
                    $col = 'col-lg-4';
                } elseif ($count % 2 === 1 && $i === $count - 1) {
                    $col = 'col-12';
                } else {
                    $col = 'col-lg-6';
                }
                echo '<div class="' . $col . ' d-flex"><div class="dp-card">';
                echo '<h4>';
                if ($c['icon'] !== '') {
                    echo '<span class="dp-icon"><i class="' . e($c['icon']) . '"></i></span>';
                }
                echo '<span>' . $numbered($c['title']) . '</span></h4>';
                if (!empty($c['body'])) {
                    echo '<div class="dp-body">';
                    $inList = false;
                    foreach ($c['body'] as $b) {
                        if ($b['type'] === 'item' && !$inList) {
                            echo '<ul>';
                            $inList = true;
                        } elseif ($b['type'] !== 'item' && $inList) {
                            echo '</ul>';
                            $inList = false;
                        }
                        if ($b['type'] === 'item') {
                            echo '<li>' . product_range_inline($b['text']) . '</li>';
                        } elseif ($b['type'] === 'highlight') {
                            echo '<p class="dp-families"><strong>' . product_range_inline($b['text']) . '</strong></p>';
                        } else {
                            echo '<p>' . product_range_inline($b['text']) . '</p>';
                        }
                    }
                    if ($inList) echo '</ul>';
                    echo '</div>';
                }
                echo '</div></div>';
            }
            echo '</div>';
        }
    }
    echo '</div>';
}
