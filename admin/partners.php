<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/partners.php';
require_once __DIR__ . '/includes/search.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $slug = (string) ($_POST['slug'] ?? '');
        $action = (string) ($_POST['action'] ?? '');
        $partners = load_partners();
        foreach ($partners as $i => $p) {
            if (($p['slug'] ?? '') !== $slug) continue;
            if ($action === 'delete') {
                array_splice($partners, $i, 1);
            } elseif ($action === 'toggle') {
                $partners[$i]['published'] = empty($p['published']);
            } elseif ($action === 'up' && $i > 0) {
                [$partners[$i - 1], $partners[$i]] = [$partners[$i], $partners[$i - 1]];
            } elseif ($action === 'down' && $i < count($partners) - 1) {
                [$partners[$i + 1], $partners[$i]] = [$partners[$i], $partners[$i + 1]];
            }
            break;
        }
        if (save_partners($partners)) {
            header('Location: partners.php?saved=1');
            exit;
        }
        $error = 'Could not save changes. Check that data/partners.json is writable.';
    }
}

$allPartners = load_partners();
$q = admin_search_query();
$partners = $allPartners;
if ($q !== '') {
    // Match the category name or any brand name inside it.
    $needle = mb_strtolower($q);
    $partners = array_values(array_filter($allPartners, function ($p) use ($needle) {
        $hay = mb_strtolower(($p['nav_name'] ?? '') . ' ' . implode(' ', array_column($p['logos'] ?? [], 'name')));
        return mb_strpos($hay, $needle) !== false;
    }));
}
$totalLogos = array_sum(array_map(function ($p) { return count($p['logos'] ?? []); }, $allPartners));
$csrf = csrf_token();
$pageTitle = 'Our Partners';
$activeNav = 'partners';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <div class="card-head">
        <div>
            <h1 class="page-title">Our Partners</h1>
            <p class="page-sub"><?= count($allPartners) ?> categories, <?= $totalLogos ?> partner logos. Shown on <a class="link-edit" href="../partners.html" target="_blank">partners.html</a> in this order. Edit a category to change its logos, certificates and detail page.</p>
        </div>
        <a href="edit.php?type=partners" class="btn"><i class="ri-add-line"></i> Add Category</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Changes saved.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php render_admin_search($q, [], 'Search categories or brand names…'); ?>
    <?php if ($q !== ''): ?>
        <p class="search-note"><?= count($partners) ?> categor<?= count($partners) === 1 ? 'y' : 'ies' ?> match “<?= e($q) ?>”</p>
    <?php endif; ?>

    <?php if (!$partners): ?>
        <div class="empty-state"><?= $q !== '' ? 'No categories or brands match your search.' : 'No partner categories yet. Click "Add Category" to create one.' ?></div>
    <?php endif; ?>

    <?php foreach ($partners as $p):
        $logos = $p['logos'] ?? [];
        $certs = count(array_filter($logos, function ($l) { return !empty($l['certificate']); }));
    ?>
    <div class="list-item">
        <div style="flex:1;min-width:260px">
            <h3>
                <a href="edit.php?type=partners&slug=<?= e($p['slug']) ?>"><?= e($p['nav_name']) ?></a>
                <?php if (!empty($p['published'])): ?>
                    <span class="badge badge-live">Live</span>
                <?php else: ?>
                    <span class="badge badge-draft">Draft</span>
                <?php endif; ?>
            </h3>
            <div class="partner-thumbs">
                <?php foreach (array_slice($logos, 0, 8) as $l): ?>
                    <img src="../<?= e($l['image']) ?>" alt="<?= e($l['name']) ?>" title="<?= e($l['name']) ?>">
                <?php endforeach; ?>
                <?php if (count($logos) > 8): ?><span class="partner-thumb-more">+<?= count($logos) - 8 ?></span><?php endif; ?>
            </div>
            <div class="item-meta">
                <span><i class="ri-image-line"></i><?= count($logos) ?> logo<?= count($logos) === 1 ? '' : 's' ?></span>
                <?php if ($certs): ?><span><i class="ri-file-pdf-2-line"></i><?= $certs ?> certificate<?= $certs === 1 ? '' : 's' ?></span><?php endif; ?>
                <?php if (empty($logos)): ?><span><i class="ri-eye-off-line"></i>Not shown on partners.html until it has logos</span><?php endif; ?>
            </div>
        </div>
        <div class="item-actions">
            <a href="edit.php?type=partners&slug=<?= e($p['slug']) ?>" class="btn btn-sm"><i class="ri-pencil-line"></i> Edit</a>
            <?php if ($q === ''): ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($p['slug']) ?>">
                <input type="hidden" name="action" value="up">
                <button class="btn btn-sm btn-secondary" title="Move up">↑</button>
            </form>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($p['slug']) ?>">
                <input type="hidden" name="action" value="down">
                <button class="btn btn-sm btn-secondary" title="Move down">↓</button>
            </form>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($p['slug']) ?>">
                <input type="hidden" name="action" value="toggle">
                <button class="btn btn-sm btn-secondary"><?= !empty($p['published']) ? 'Unpublish' : 'Publish' ?></button>
            </form>
            <form method="post" onsubmit="return confirm('Delete this partner category, its logos list and its detail page content? Uploaded files are kept.');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($p['slug']) ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-sm btn-danger-outline"><i class="ri-delete-bin-line"></i> Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<style>
    .partner-thumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 10px 0 6px; }
    .partner-thumbs img { width: 72px; height: 36px; object-fit: contain; background: #fff; border: 1px solid #eee; border-radius: 6px; padding: 3px; }
    .partner-thumb-more { display: inline-flex; align-items: center; justify-content: center; height: 36px; min-width: 36px; padding: 0 8px; border-radius: 18px; font-size: 12px; font-weight: 700; color: #6b7280; background: #f3f4f6; }
</style>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
