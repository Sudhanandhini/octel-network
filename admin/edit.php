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

$errors = [];
$blankRecord = [
    'slug' => '', 'nav_name' => '', 'page_title' => '', 'published' => false,
    'hero_image' => '', 'intro_paragraphs' => '', 'portfolio_heading' => '',
    'portfolio_subtitle' => '', 'portfolio_cards' => '', 'whatwedo_intro' => '',
    'whatwedo_items' => '', 'summary_bullets' => '', 'cta_heading' => '', 'cta_text' => '',
    'content_blocks' => [],
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
        $updated['intro_paragraphs'] = trim($_POST['intro_paragraphs'] ?? '');
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

        if ($updated['nav_name'] === '') {
            $errors[] = 'Name cannot be empty.';
        } elseif ($isNew) {
            $existingSlugs = array_column($items, 'slug');
            $updated['slug'] = unique_slug(slugify($updated['nav_name']), $existingSlugs);
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
                header('Location: dashboard.php?saved=1');
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

        <label for="whatwedo_intro">"What Octel Networks Does" intro line</label>
        <input type="text" id="whatwedo_intro" name="whatwedo_intro" value="<?= e($record['whatwedo_intro']) ?>">

        <label for="whatwedo_items">"What Octel Networks Does" items
            <span class="hint">One item per line, formatted as: Title | Description</span>
        </label>
        <textarea id="whatwedo_items" name="whatwedo_items" rows="6"><?= e($record['whatwedo_items']) ?></textarea>

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
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
