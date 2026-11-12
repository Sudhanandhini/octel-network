<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/distributors.php';
require_once __DIR__ . '/../includes/solutions.php';
require_once __DIR__ . '/../includes/partners.php';
require_login();

$requestedType = $_GET['type'] ?? $_POST['type'] ?? '';
$type = in_array($requestedType, ['solutions', 'partners'], true) ? $requestedType : 'distributors';
$slug = trim($_GET['slug'] ?? $_POST['slug'] ?? '');

if ($type === 'solutions') {
    $items = load_solutions();
    $saveFn = 'save_solutions';
    $collectionLabel = 'Solution';
} elseif ($type === 'partners') {
    $items = load_partners();
    $saveFn = 'save_partners';
    $collectionLabel = 'Partner Category';
} else {
    $items = load_distributors();
    $saveFn = 'save_distributors';
    $collectionLabel = 'Distributor';
}

// Every collection has a generic fallback detail page (partner-details.html,
// solution-details.html, partner-category-details.html) for items without a
// hand-built <slug>.html page, so "Add New" works for all three.
$isNew = $slug === '';

$index = null;
foreach ($items as $i => $d) {
    if ($d['slug'] === $slug) {
        $index = $i;
        break;
    }
}

if ($index === null && !$isNew) {
    http_response_code(404);
    exit('Not found.');
}

/**
 * Each image block carries a stable client-side id, used as the name of its
 * own <input type=file name="content_block_image_<id>">. Walk the (possibly
 * nested, inside row columns) block tree and swap in any uploaded file's
 * saved path as that block's src.
 */
function process_block_image_uploads(array &$blocks): void {
    foreach ($blocks as &$block) {
        if (!is_array($block)) continue;

        if (($block['type'] ?? '') === 'image') {
            $id = (string) ($block['id'] ?? '');
            $fieldName = 'content_block_image_' . $id;
            if ($id !== '' && !empty($_FILES[$fieldName]['name'])) {
                $file = $_FILES[$fieldName];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (in_array($ext, $allowed, true) && $file['size'] <= 5 * 1024 * 1024) {
                        $uploadDir = __DIR__ . '/../assets/img/uploads';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        $safeId = preg_replace('/[^a-zA-Z0-9]/', '', $id);
                        $filename = 'block-' . $safeId . '-' . time() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                            $block['src'] = 'assets/img/uploads/' . $filename;
                        }
                    }
                }
            }
        } elseif (($block['type'] ?? '') === 'row' && !empty($block['columns']) && is_array($block['columns'])) {
            foreach ($block['columns'] as &$column) {
                if (is_array($column)) {
                    process_block_image_uploads($column);
                }
            }
            unset($column);
        }
    }
    unset($block);
}

/**
 * Rebuild a partner category's logo list from the form: existing rows (in the
 * order they were submitted, minus any ticked for removal) followed by any
 * newly uploaded files, each named after its file until renamed.
 */
function process_partner_logos(array &$record, array &$errors): void {
    $logos = [];
    foreach ($_POST['logos'] ?? [] as $key => $row) {
        if (!is_array($row) || !empty($row['remove'])) continue;
        $image = trim($row['image'] ?? '');
        if (strpos($image, 'assets/img/') !== 0) continue;
        $logo = ['name' => trim($row['name'] ?? ''), 'image' => $image];
        $certificate = empty($row['remove_certificate']) ? trim($row['certificate'] ?? '') : '';
        $uploaded = upload_partner_certificate($key, $logo['name'], $errors);
        if ($uploaded !== null) $certificate = $uploaded;
        if (strpos($certificate, 'assets/pdf/') === 0) $logo['certificate'] = $certificate;
        $logos[] = $logo;
    }

    $files = $_FILES['logo_files'] ?? null;
    if ($files && is_array($files['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $uploadDir = __DIR__ . '/../assets/img/logo';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        foreach ($files['name'] as $i => $originalName) {
            if ($originalName === '') continue;
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload of "' . $originalName . '" failed. Please try again.';
                continue;
            }
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $errors[] = '"' . $originalName . '" must be one of: ' . implode(', ', $allowed) . '.';
                continue;
            }
            if ($files['size'][$i] > 5 * 1024 * 1024) {
                $errors[] = '"' . $originalName . '" must be smaller than 5MB.';
                continue;
            }
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $filename = slugify($baseName) . '-' . time() . '-' . $i . '.' . $ext;
            if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . '/' . $filename)) {
                $logos[] = [
                    'name' => ucwords(trim(preg_replace('/[-_]+/', ' ', $baseName))),
                    'image' => 'assets/img/logo/' . $filename,
                ];
            } else {
                $errors[] = 'Could not save "' . $originalName . '".';
            }
        }
    }

    $record['logos'] = $logos;
}

/**
 * Save the certificate PDF uploaded for one logo row, if any. Returns its
 * path, or null when nothing was uploaded or the upload was rejected.
 */
function upload_partner_certificate($key, string $brandName, array &$errors): ?string {
    $files = $_FILES['certificate_files'] ?? null;
    if (!$files || !isset($files['name'][$key]) || $files['name'][$key] === '') return null;

    $originalName = $files['name'][$key];
    if ($files['error'][$key] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload of "' . $originalName . '" failed. Please try again.';
        return null;
    }
    if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'pdf'
        || file_get_contents($files['tmp_name'][$key], false, null, 0, 5) !== '%PDF-') {
        $errors[] = '"' . $originalName . '" must be a PDF file.';
        return null;
    }
    if ($files['size'][$key] > 10 * 1024 * 1024) {
        $errors[] = '"' . $originalName . '" must be smaller than 10MB.';
        return null;
    }

    $uploadDir = __DIR__ . '/../assets/pdf';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $baseName = slugify($brandName !== '' ? $brandName : pathinfo($originalName, PATHINFO_FILENAME));
    $filename = $baseName . '-certificate-' . time() . '-' . $key . '.pdf';
    if (move_uploaded_file($files['tmp_name'][$key], $uploadDir . '/' . $filename)) {
        return 'assets/pdf/' . $filename;
    }
    $errors[] = 'Could not save "' . $originalName . '".';
    return null;
}

/**
 * Rebuild a distributor's listing-card slider images from the form: existing
 * slides (in submitted order, minus any ticked for removal) followed by any
 * newly uploaded files.
 */
function process_card_slides(array &$record, array &$errors): void {
    $slides = [];
    foreach ($_POST['card_slides'] ?? [] as $row) {
        if (!is_array($row) || !empty($row['remove'])) continue;
        $image = trim($row['image'] ?? '');
        if (strpos($image, 'assets/img/') !== 0) continue;
        $slides[] = $image;
    }

    $files = $_FILES['card_slide_files'] ?? null;
    if ($files && is_array($files['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $uploadDir = __DIR__ . '/../assets/img/slides';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        foreach ($files['name'] as $i => $originalName) {
            if ($originalName === '') continue;
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload of "' . $originalName . '" failed. Please try again.';
                continue;
            }
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $errors[] = '"' . $originalName . '" must be one of: ' . implode(', ', $allowed) . '.';
                continue;
            }
            if ($files['size'][$i] > 5 * 1024 * 1024) {
                $errors[] = '"' . $originalName . '" must be smaller than 5MB.';
                continue;
            }
            $prefix = $record['slug'] !== '' ? $record['slug'] : 'slide';
            $filename = $prefix . '-' . slugify(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . time() . '-' . $i . '.' . $ext;
            if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . '/' . $filename)) {
                $slides[] = 'assets/img/slides/' . $filename;
            } else {
                $errors[] = 'Could not save "' . $originalName . '".';
            }
        }
    }

    $record['card_slides'] = $slides;
}

$errors = [];
$blankRecord = [
    'slug' => '', 'nav_name' => '', 'page_title' => '', 'published' => false,
    'hero_image' => '', 'intro_heading' => '', 'intro_paragraphs' => '', 'portfolio_heading' => '',
    'whatwedo_heading' => '', 'built_for' => '', 'outcome' => '',
    'portfolio_subtitle' => '', 'portfolio_cards' => '', 'whatwedo_intro' => '',
    'whatwedo_items' => '', 'summary_bullets' => '', 'cta_heading' => '', 'cta_text' => '',
    'content_blocks' => [], 'logos' => [],
    'product_range' => '', 'hide_portfolio_cards' => false, 'card_slides' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $current = $isNew ? $blankRecord : $items[$index];

        $updated = $current;
        $updated['nav_name'] = trim($_POST['nav_name'] ?? '');
        $updated['page_title'] = trim($_POST['page_title'] ?? '');
        $updated['published'] = isset($_POST['published']);
        $updated['intro_heading'] = trim($_POST['intro_heading'] ?? '');
        $updated['intro_paragraphs'] = trim($_POST['intro_paragraphs'] ?? '');
        $updated['whatwedo_heading'] = trim($_POST['whatwedo_heading'] ?? '');
        $updated['built_for'] = trim($_POST['built_for'] ?? '');
        $updated['outcome'] = trim($_POST['outcome'] ?? '');
        $updated['portfolio_heading'] = trim($_POST['portfolio_heading'] ?? '');
        $updated['portfolio_subtitle'] = trim($_POST['portfolio_subtitle'] ?? '');
        $updated['portfolio_cards'] = trim($_POST['portfolio_cards'] ?? '');
        $updated['whatwedo_intro'] = trim($_POST['whatwedo_intro'] ?? '');
        $updated['whatwedo_items'] = trim($_POST['whatwedo_items'] ?? '');
        $updated['summary_bullets'] = trim($_POST['summary_bullets'] ?? '');
        $updated['cta_heading'] = trim($_POST['cta_heading'] ?? '');
        $updated['cta_text'] = trim($_POST['cta_text'] ?? '');
        $updated['hero_image'] = trim($_POST['hero_image'] ?? $current['hero_image'] ?? '');

        $decodedBlocks = json_decode($_POST['content_blocks'] ?? '[]', true);
        $updated['content_blocks'] = is_array($decodedBlocks) ? $decodedBlocks : [];
        process_block_image_uploads($updated['content_blocks']);

        if ($type === 'partners') {
            process_partner_logos($updated, $errors);
        }

        if ($updated['nav_name'] === '') {
            $errors[] = 'Name cannot be empty.';
        } elseif ($isNew) {
            $existingSlugs = array_column($items, 'slug');
            $updated['slug'] = unique_slug(slugify($updated['nav_name']), $existingSlugs);
        }

        if ($type === 'distributors') {
            $updated['product_range'] = trim($_POST['product_range'] ?? '');
            $updated['hide_portfolio_cards'] = isset($_POST['hide_portfolio_cards']);
            process_card_slides($updated, $errors);
        }

        if (!empty($_FILES['hero_image_file']['name'])) {
            $file = $_FILES['hero_image_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Image upload failed. Please try again.';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (!in_array($ext, $allowed, true)) {
                    $errors[] = 'Image must be one of: ' . implode(', ', $allowed) . '.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = 'Image must be smaller than 5MB.';
                } else {
                    $uploadDir = __DIR__ . '/../assets/img/uploads';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $filenameSlug = $updated['slug'] !== '' ? $updated['slug'] : 'item';
                    $filename = $filenameSlug . '-' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                        $updated['hero_image'] = 'assets/img/uploads/' . $filename;
                    } else {
                        $errors[] = 'Could not save the uploaded image.';
                    }
                }
            }
        }

        if (empty($errors)) {
            if ($isNew) {
                $items[] = $updated;
            } else {
                $items[$index] = $updated;
            }
            if ($saveFn($items)) {
                header('Location: ' . ($type === 'partners' ? 'partners.php' : 'dashboard.php') . '?saved=1');
                exit;
            }
            $errors[] = 'Could not save changes. Check file permissions on the data folder.';
        }

        // Keep the form populated with attempted values on error.
        $record = $updated;
    }
}

if (!isset($record)) {
    $record = $isNew ? $blankRecord : $items[$index];
}

$csrf = csrf_token();
$pageTitle = $isNew ? 'Add ' . $collectionLabel : 'Edit ' . $record['nav_name'];
$activeNav = $type === 'solutions' ? 'solutions' : ($type === 'partners' ? 'partners' : 'distributors');
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <h1 class="page-title"><?= $isNew ? 'Add New ' . e($collectionLabel) : 'Edit ' . e($collectionLabel) . ' — ' . e($record['nav_name']) ?></h1>
    <p class="page-sub"><?= $isNew ? 'A URL-friendly link is generated automatically from the name.' : "Changes appear immediately on the listing page and this item's detail page." ?></p>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">

        <div class="checkbox-row">
            <input type="checkbox" id="published" name="published" <?= !empty($record['published']) ? 'checked' : '' ?>>
            <label for="published" style="margin:0;">Published (shows a live link on the listing page)</label>
        </div>

        <label for="nav_name">Name</label>
        <input type="text" id="nav_name" name="nav_name" value="<?= e($record['nav_name']) ?>" required>

        <?php if ($type === 'partners'): ?>
        <fieldset class="logo-manager">
            <legend>Partner logos</legend>
            <p class="hint">Shown under this category on the Our Partners page, in this order. Use the arrows to reorder, tick "Remove" to delete, then save. Attach a certificate PDF (up to 10MB) to show it when visitors hover over that logo.</p>
            <div id="logo-list" class="reorder-list">
                <?php foreach ($record['logos'] ?? [] as $i => $logo): ?>
                <div class="logo-row">
                    <img src="../<?= e($logo['image']) ?>" alt="">
                    <input type="hidden" name="logos[<?= $i ?>][image]" value="<?= e($logo['image']) ?>">
                    <input type="hidden" name="logos[<?= $i ?>][certificate]" value="<?= e($logo['certificate'] ?? '') ?>">
                    <input type="text" name="logos[<?= $i ?>][name]" value="<?= e($logo['name']) ?>" placeholder="Brand name">
                    <button type="button" class="logo-move" data-dir="-1" title="Move up">↑</button>
                    <button type="button" class="logo-move" data-dir="1" title="Move down">↓</button>
                    <label class="logo-remove"><input type="checkbox" name="logos[<?= $i ?>][remove]" value="1"> Remove</label>
                    <div class="logo-cert">
                        <span class="logo-cert-label">Certificate (PDF):</span>
                        <?php if (!empty($logo['certificate'])): ?>
                        <a href="../<?= e($logo['certificate']) ?>" target="_blank" rel="noopener"><?= e(basename($logo['certificate'])) ?></a>
                        <label class="logo-remove"><input type="checkbox" name="logos[<?= $i ?>][remove_certificate]" value="1"> Remove certificate</label>
                        <span class="hint">or replace:</span>
                        <?php else: ?>
                        <span class="hint">none —</span>
                        <?php endif; ?>
                        <input type="file" name="certificate_files[<?= $i ?>]" accept=".pdf,application/pdf">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($record['logos'])): ?>
            <p class="hint">No logos yet.</p>
            <?php endif; ?>
            <label for="logo_files">Add logos
                <span class="hint">Select one or more images (JPG, PNG, WEBP or GIF, up to 5MB each). Each is named after its file; you can rename it after saving. Logos with little empty space around them look best.</span>
            </label>
            <input type="file" id="logo_files" name="logo_files[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple>
        </fieldset>
        <?php endif; ?>

        <?php if ($type === 'partners' || $type === 'distributors'): ?>
        <style>
            .logo-manager { border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px 20px; margin: 20px 0; }
            .logo-manager legend { font-weight: 700; padding: 0 6px; }
            .logo-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
            .logo-row img { width: 110px; height: 44px; object-fit: contain; background: #fff; border: 1px solid #eee; border-radius: 6px; padding: 4px; flex-shrink: 0; }
            .logo-row input[type="text"] { flex: 1; margin: 0; }
            .logo-row .logo-move { width: 34px; height: 34px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; cursor: pointer; flex-shrink: 0; }
            .logo-row .logo-remove { display: flex; align-items: center; gap: 5px; margin: 0; font-weight: 400; white-space: nowrap; }
            .logo-row .logo-remove input { width: auto; margin: 0; }
            .logo-row:has(> .logo-remove input:checked) { opacity: .45; }
            #logo-list .logo-row { flex-wrap: wrap; }
            .logo-row .logo-cert { flex-basis: 100%; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding-left: 120px; font-size: 13px; }
            .logo-row .logo-cert-label { font-weight: 600; }
            .logo-row .logo-cert a { color: #e30613; word-break: break-all; }
            .logo-row .logo-cert input[type="file"] { width: auto; margin: 0; }
            .logo-row .logo-cert .hint { margin: 0; }
            .logo-row img.slide-thumb { width: 160px; height: 64px; object-fit: cover; padding: 0; background: #111; }
            .logo-row .slide-path { flex: 1; font-size: 13px; color: #6b7280; word-break: break-all; }
        </style>
        <script>
            document.addEventListener('click', function (ev) {
                var btn = ev.target.closest('.reorder-list .logo-move');
                if (!btn) return;
                var row = btn.closest('.logo-row');
                if (btn.dataset.dir === '-1' && row.previousElementSibling) {
                    row.parentNode.insertBefore(row, row.previousElementSibling);
                } else if (btn.dataset.dir === '1' && row.nextElementSibling) {
                    row.parentNode.insertBefore(row.nextElementSibling, row);
                }
            });
        </script>
        <?php endif; ?>

        <label for="page_title">Page title (heading shown on the detail page)</label>
        <input type="text" id="page_title" name="page_title" value="<?= e($record['page_title']) ?>">

        <label for="hero_image">Hero image path</label>
        <input type="text" id="hero_image" name="hero_image" value="<?= e($record['hero_image']) ?>">
        <?php if (!empty($record['hero_image'])): ?>
            <img class="current-image" src="../<?= e($record['hero_image']) ?>" alt="Current image">
        <?php endif; ?>
        <label for="hero_image_file">...or upload a new image
            <span class="hint">JPG, PNG, WEBP or GIF, up to 5MB. Replaces the path above.</span>
        </label>
        <input type="file" id="hero_image_file" name="hero_image_file" accept="image/*">

        <?php if ($type === 'distributors'): ?>
        <fieldset class="logo-manager">
            <legend>Listing card slider images</legend>
            <p class="hint">When this list has images, the card on the Distribution page shows them as an auto-playing slider instead of the hero image. Use the arrows to reorder, tick "Remove" to delete, then save.</p>
            <div id="slide-list" class="reorder-list">
                <?php foreach ($record['card_slides'] ?? [] as $i => $slide): ?>
                <div class="logo-row">
                    <img class="slide-thumb" src="../<?= e($slide) ?>" alt="">
                    <input type="hidden" name="card_slides[<?= $i ?>][image]" value="<?= e($slide) ?>">
                    <span class="slide-path"><?= e(basename($slide)) ?></span>
                    <button type="button" class="logo-move" data-dir="-1" title="Move up">↑</button>
                    <button type="button" class="logo-move" data-dir="1" title="Move down">↓</button>
                    <label class="logo-remove"><input type="checkbox" name="card_slides[<?= $i ?>][remove]" value="1"> Remove</label>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($record['card_slides'])): ?>
            <p class="hint">No slider images. The card shows the hero image.</p>
            <?php endif; ?>
            <label for="card_slide_files">Add slider images
                <span class="hint">Select one or more images (JPG, PNG, WEBP or GIF, up to 5MB each). New images are added to the end of the list. Wide images (about 2:1) fill the card best.</span>
            </label>
            <input type="file" id="card_slide_files" name="card_slide_files[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple>
        </fieldset>
        <?php endif; ?>

        <label for="intro_heading">Introduction heading</label>
        <input type="text" id="intro_heading" name="intro_heading" value="<?= e($record['intro_heading'] ?? '') ?>">

        <label for="intro_paragraphs">Introduction
            <span class="hint">One or more paragraphs. Leave a blank line between paragraphs.</span>
        </label>
        <textarea id="intro_paragraphs" name="intro_paragraphs" rows="6"><?= e($record['intro_paragraphs']) ?></textarea>

        <label for="portfolio_heading">Product/portfolio heading</label>
        <input type="text" id="portfolio_heading" name="portfolio_heading" value="<?= e($record['portfolio_heading']) ?>">

        <label for="portfolio_subtitle">Product/portfolio subtitle</label>
        <input type="text" id="portfolio_subtitle" name="portfolio_subtitle" value="<?= e($record['portfolio_subtitle']) ?>">

        <label for="portfolio_cards">Product/portfolio cards
            <span class="hint">One card per line, formatted as: icon-class | Title | Description. Icons come from remixicon.com (e.g. ri-router-line).</span>
        </label>
        <textarea id="portfolio_cards" name="portfolio_cards" rows="8"><?= e($record['portfolio_cards']) ?></textarea>

        <?php if ($type === 'distributors'): ?>
        <label for="product_range">Product range
            <span class="hint">Detailed product lists shown below the portfolio cards on the detail page. One entry per line:</span>
        </label>
        <pre class="format-help">## 1. Group heading          a heading above a set of cards ("1." becomes a number badge)
### Card title               starts a new card
### ri-camera-line | Title   a card with an icon (remixicon.com)
- List item                  a bullet in the current card
Plain text                   a paragraph in the current card
! Highlighted text           a bold, boxed line in the current card
&gt; Text                       a paragraph outside the cards
---                          start a new row of cards

Inside any line: **bold text** and [[source tag]]</pre>
        <textarea id="product_range" name="product_range" rows="18" class="mono"><?= e($record['product_range'] ?? '') ?></textarea>

        <div class="checkbox-row">
            <input type="checkbox" id="hide_portfolio_cards" name="hide_portfolio_cards" <?= !empty($record['hide_portfolio_cards']) ? 'checked' : '' ?>>
            <label for="hide_portfolio_cards" style="margin:0;">Hide the portfolio cards above (use when the product range already covers them). The heading and subtitle stay.</label>
        </div>
        <style>
            .format-help { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 14px; font-size: 12.5px; line-height: 1.6; white-space: pre-wrap; margin: 0 0 8px; }
            textarea.mono { font-family: Consolas, Menlo, monospace; font-size: 13px; }
        </style>
        <?php endif; ?>

        <label for="whatwedo_heading">"Why Organizations Choose Octel" heading</label>
        <input type="text" id="whatwedo_heading" name="whatwedo_heading" value="<?= e($record['whatwedo_heading'] ?? '') ?>">

        <label for="whatwedo_intro">"What Octel Networks Does" intro line</label>
        <input type="text" id="whatwedo_intro" name="whatwedo_intro" value="<?= e($record['whatwedo_intro']) ?>">

        <label for="whatwedo_items">"What Octel Networks Does" items
            <span class="hint">One item per line, formatted as: Title | Description</span>
        </label>
        <textarea id="whatwedo_items" name="whatwedo_items" rows="6"><?= e($record['whatwedo_items']) ?></textarea>

        <label for="built_for">Built for
            <span class="hint">Separate items with " | " (e.g. Precision | Reliability | Demanding Environments)</span>
        </label>
        <input type="text" id="built_for" name="built_for" value="<?= e($record['built_for'] ?? '') ?>">

        <label for="outcome">Outcome</label>
        <textarea id="outcome" name="outcome" rows="2"><?= e($record['outcome'] ?? '') ?></textarea>

        <label for="summary_bullets">Listing page card bullets
            <span class="hint">One short bullet per line. Shown on the summary card on the listing page.</span>
        </label>
        <textarea id="summary_bullets" name="summary_bullets" rows="3"><?= e($record['summary_bullets']) ?></textarea>

        <label for="cta_heading">Contact box heading</label>
        <input type="text" id="cta_heading" name="cta_heading" value="<?= e($record['cta_heading']) ?>">

        <label for="cta_text">Contact box subtext</label>
        <input type="text" id="cta_text" name="cta_text" value="<?= e($record['cta_text']) ?>">

        <?php require __DIR__ . '/includes/block-editor.php'; ?>

        <div class="form-actions">
            <button type="submit" class="btn"><?= $isNew ? 'Create' : 'Save Changes' ?></button>
            <a href="<?= $type === 'partners' ? 'partners.php' : 'dashboard.php' ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
