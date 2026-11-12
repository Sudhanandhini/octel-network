<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/clients.php';
require_login();

const CLIENT_LOGO_TYPES = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
const CLIENT_LOGO_DIR = 'assets/img/clients/all';

$sectors = load_client_sectors();
$slug = trim($_GET['slug'] ?? $_POST['slug'] ?? '');
$isNew = $slug === '';

$index = null;
foreach ($sectors as $i => $s) {
    if (($s['slug'] ?? '') === $slug) {
        $index = $i;
        break;
    }
}
if ($index === null && !$isNew) {
    http_response_code(404);
    exit('Not found.');
}

/**
 * Validate and save one uploaded logo. $files is a $_FILES entry using array
 * syntax (name[], tmp_name[], ...) and $key the index inside it. Returns the
 * saved path, or null when nothing was uploaded or the file was rejected.
 */
function save_client_logo(array $files, $key, string $clientName, array &$errors): ?string {
    if (!isset($files['name'][$key]) || $files['name'][$key] === '') return null;
    $originalName = $files['name'][$key];
    if ($files['error'][$key] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload of "' . $originalName . '" failed. Please try again.';
        return null;
    }
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, CLIENT_LOGO_TYPES, true) || @getimagesize($files['tmp_name'][$key]) === false) {
        $errors[] = '"' . $originalName . '" must be an image: ' . implode(', ', CLIENT_LOGO_TYPES) . '.';
        return null;
    }
    if ($files['size'][$key] > 5 * 1024 * 1024) {
        $errors[] = '"' . $originalName . '" must be smaller than 5MB.';
        return null;
    }
    $dir = __DIR__ . '/../' . CLIENT_LOGO_DIR;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $base = slugify($clientName !== '' ? $clientName : pathinfo($originalName, PATHINFO_FILENAME));
    $filename = $base . '-' . time() . '-' . preg_replace('/[^a-z0-9]/i', '', (string) $key) . '.' . $ext;
    if (move_uploaded_file($files['tmp_name'][$key], $dir . '/' . $filename)) {
        return CLIENT_LOGO_DIR . '/' . $filename;
    }
    $errors[] = 'Could not save "' . $originalName . '".';
    return null;
}

$errors = [];
$blank = ['slug' => '', 'published' => true, 'nav_name' => '', 'short_name' => '', 'clients' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $updated = $isNew ? $blank : $sectors[$index];
        $updated['nav_name'] = trim($_POST['nav_name'] ?? '');
        $updated['short_name'] = trim($_POST['short_name'] ?? '');
        $updated['published'] = isset($_POST['published']);
        if ($updated['short_name'] === '') $updated['short_name'] = $updated['nav_name'];

        // Existing rows, in submitted order, minus any ticked for removal.
        $clients = [];
        $replaceFiles = $_FILES['replace_logo'] ?? ['name' => []];
        foreach ($_POST['clients'] ?? [] as $key => $row) {
            if (!is_array($row) || !empty($row['remove'])) continue;
            $name = trim($row['name'] ?? '');
            $image = empty($row['remove_logo']) ? trim($row['image'] ?? '') : '';
            if ($image !== '' && strpos($image, 'assets/img/') !== 0) $image = '';
            $uploaded = save_client_logo($replaceFiles, $key, $name, $errors);
            if ($uploaded !== null) $image = $uploaded;
            if ($name === '' && $image === '') continue;
            $clients[] = ['name' => $name, 'image' => $image];
        }

        // New clients from uploaded logos, named after the file until renamed.
        $newFiles = $_FILES['logo_files'] ?? null;
        if ($newFiles && is_array($newFiles['name'])) {
            foreach ($newFiles['name'] as $i => $originalName) {
                if ($originalName === '') continue;
                $name = ucwords(trim(preg_replace('/[-_]+/', ' ', pathinfo($originalName, PATHINFO_FILENAME))));
                $path = save_client_logo($newFiles, $i, $name, $errors);
                if ($path !== null) $clients[] = ['name' => $name, 'image' => $path];
            }
        }

        // New clients without a logo, one name per line.
        foreach (parse_lines($_POST['new_names'] ?? '') as $name) {
            $clients[] = ['name' => $name, 'image' => ''];
        }

        $updated['clients'] = $clients;

        if ($updated['nav_name'] === '') {
            $errors[] = 'Sector name cannot be empty.';
        } elseif ($isNew) {
            $updated['slug'] = unique_slug(slugify($updated['nav_name']), array_column($sectors, 'slug'));
        }

        if (empty($errors)) {
            if ($isNew) {
                $sectors[] = $updated;
            } else {
                $sectors[$index] = $updated;
            }
            if (save_client_sectors($sectors)) {
                header('Location: clients.php?saved=1');
                exit;
            }
            $errors[] = 'Could not save changes. Check that data/clients.json is writable.';
        }
        $record = $updated;
    }
}

if (!isset($record)) {
    $record = $isNew ? $blank : $sectors[$index];
}

$csrf = csrf_token();
$pageTitle = $isNew ? 'Add Client Sector' : 'Edit ' . $record['nav_name'];
$activeNav = 'clients';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <h1 class="page-title"><?= $isNew ? 'Add Client Sector' : 'Edit Sector — ' . e($record['nav_name']) ?></h1>
    <p class="page-sub">Changes appear immediately on <a class="link-edit" href="../clients.html" target="_blank">clients.html</a>.</p>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">

        <div class="checkbox-row">
            <input type="checkbox" id="published" name="published" <?= !empty($record['published']) ? 'checked' : '' ?>>
            <label for="published" style="margin:0;">Show this sector on the Our Clients page</label>
        </div>

        <label for="nav_name">Sector name
            <span class="hint">Heading shown above this sector's clients, e.g. "Banking, Finance &amp; Forex".</span>
        </label>
        <input type="text" id="nav_name" name="nav_name" value="<?= e($record['nav_name']) ?>" required>

        <label for="short_name">Filter button label
            <span class="hint">Short label for the filter button at the top of the page, e.g. "Banking &amp; Finance". Leave blank to use the sector name.</span>
        </label>
        <input type="text" id="short_name" name="short_name" value="<?= e($record['short_name'] ?? '') ?>">

        <fieldset class="logo-manager">
            <legend>Clients</legend>
            <p class="hint">Shown in this order. Use the arrows to reorder, tick "Remove" to delete a client, then save. Clients without a logo show their initials in a red circle until you upload one.</p>
            <div id="client-list" class="reorder-list">
                <?php foreach ($record['clients'] ?? [] as $i => $c): ?>
                <div class="logo-row">
                    <?php if (!empty($c['image'])): ?>
                        <img src="../<?= e($c['image']) ?>" alt="">
                    <?php else: ?>
                        <span class="no-logo"><?= e(client_initials($c['name'])) ?></span>
                    <?php endif; ?>
                    <input type="hidden" name="clients[<?= $i ?>][image]" value="<?= e($c['image'] ?? '') ?>">
                    <input type="text" name="clients[<?= $i ?>][name]" value="<?= e($c['name']) ?>" placeholder="Client name">
                    <button type="button" class="logo-move" data-dir="-1" title="Move up">↑</button>
                    <button type="button" class="logo-move" data-dir="1" title="Move down">↓</button>
                    <label class="logo-remove"><input type="checkbox" name="clients[<?= $i ?>][remove]" value="1"> Remove</label>
                    <div class="logo-cert">
                        <span class="logo-cert-label"><?= !empty($c['image']) ? 'Replace logo:' : 'Upload logo:' ?></span>
                        <input type="file" name="replace_logo[<?= $i ?>]" accept=".jpg,.jpeg,.png,.webp,.gif">
                        <?php if (!empty($c['image'])): ?>
                        <label class="logo-remove"><input type="checkbox" name="clients[<?= $i ?>][remove_logo]" value="1"> Remove logo (show initials)</label>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($record['clients'])): ?>
            <p class="hint">No clients yet.</p>
            <?php endif; ?>

            <label for="logo_files">Add clients with logos
                <span class="hint">Select one or more logo images (JPG, PNG, WEBP or GIF, up to 5MB each). Each becomes a new client named after its file; you can rename it after saving.</span>
            </label>
            <input type="file" id="logo_files" name="logo_files[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple>

            <label for="new_names">Add clients without logos
                <span class="hint">One client name per line. You can upload their logos later.</span>
            </label>
            <textarea id="new_names" name="new_names" rows="3" placeholder="e.g. Domlur Club"></textarea>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="btn"><?= $isNew ? 'Create' : 'Save Changes' ?></button>
            <a href="clients.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<style>
    .logo-manager { border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px 20px; margin: 20px 0; }
    .logo-manager legend { font-weight: 700; padding: 0 6px; }
    .logo-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
    .logo-row img, .logo-row .no-logo { width: 110px; height: 44px; flex-shrink: 0; border-radius: 6px; }
    .logo-row img { object-fit: contain; background: #fff; border: 1px solid #eee; padding: 4px; }
    .logo-row .no-logo { display: flex; align-items: center; justify-content: center; font-weight: 700; color: #e30016; background: rgba(227, 0, 22, .06); border: 1px dashed rgba(227, 0, 22, .35); }
    .logo-row input[type="text"] { flex: 1; margin: 0; }
    .logo-row .logo-move { width: 34px; height: 34px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; cursor: pointer; flex-shrink: 0; }
    .logo-row .logo-remove { display: flex; align-items: center; gap: 5px; margin: 0; font-weight: 400; white-space: nowrap; }
    .logo-row .logo-remove input { width: auto; margin: 0; }
    .logo-row:has(> .logo-remove input:checked) { opacity: .45; }
    .logo-row .logo-cert { flex-basis: 100%; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding-left: 120px; font-size: 13px; }
    .logo-row .logo-cert-label { font-weight: 600; }
    .logo-row .logo-cert input[type="file"] { width: auto; margin: 0; }
    @media (max-width: 640px) { .logo-row .logo-cert { padding-left: 0; } }
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
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
