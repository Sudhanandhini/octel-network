<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/clients.php';
require_once __DIR__ . '/includes/search.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $slug = (string) ($_POST['slug'] ?? '');
        $action = (string) ($_POST['action'] ?? '');
        $sectors = load_client_sectors();
        foreach ($sectors as $i => $sector) {
            if (($sector['slug'] ?? '') !== $slug) continue;
            if ($action === 'delete') {
                array_splice($sectors, $i, 1);
            } elseif ($action === 'toggle') {
                $sectors[$i]['published'] = empty($sector['published']);
            } elseif ($action === 'up' && $i > 0) {
                [$sectors[$i - 1], $sectors[$i]] = [$sectors[$i], $sectors[$i - 1]];
            } elseif ($action === 'down' && $i < count($sectors) - 1) {
                [$sectors[$i + 1], $sectors[$i]] = [$sectors[$i], $sectors[$i + 1]];
            }
            break;
        }
        if (save_client_sectors($sectors)) {
            header('Location: clients.php?saved=1');
            exit;
        }
        $error = 'Could not save changes. Check that data/clients.json is writable.';
    }
}

$allSectors = load_client_sectors();
$q = admin_search_query();
$sectors = $allSectors;
if ($q !== '') {
    // Match the sector name or any client name inside it.
    $needle = mb_strtolower($q);
    $sectors = array_values(array_filter($allSectors, function ($s) use ($needle) {
        $hay = mb_strtolower(($s['nav_name'] ?? '') . ' ' . ($s['short_name'] ?? '') . ' ' . implode(' ', array_column($s['clients'] ?? [], 'name')));
        return mb_strpos($hay, $needle) !== false;
    }));
}
$totalClients = array_sum(array_map(function ($s) { return count($s['clients'] ?? []); }, $allSectors));
$csrf = csrf_token();
$pageTitle = 'Our Clients';
$activeNav = 'clients';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <div class="card-head">
        <div>
            <h1 class="page-title">Our Clients</h1>
            <p class="page-sub"><?= count($allSectors) ?> sectors, <?= $totalClients ?> clients. Shown on <a class="link-edit" href="../clients.html" target="_blank">clients.html</a> in this order; each sector also gets a filter button.</p>
        </div>
        <a href="client-edit.php" class="btn"><i class="ri-add-line"></i> Add Sector</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Changes saved.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php render_admin_search($q, [], 'Search sectors or client names…'); ?>
    <?php if ($q !== ''): ?>
        <p class="search-note"><?= count($sectors) ?> sector<?= count($sectors) === 1 ? '' : 's' ?> match “<?= e($q) ?>”</p>
    <?php endif; ?>

    <?php if (!$sectors): ?>
        <div class="empty-state"><?= $q !== '' ? 'No sectors or clients match your search.' : 'No sectors yet. Click "Add Sector" to create one.' ?></div>
    <?php endif; ?>

    <?php foreach ($sectors as $sector):
        $clients = $sector['clients'] ?? [];
        $noLogo = count(array_filter($clients, function ($c) { return empty($c['image']); }));
    ?>
    <div class="list-item">
        <div style="flex:1;min-width:260px">
            <h3>
                <a href="client-edit.php?slug=<?= e($sector['slug']) ?>"><?= e($sector['nav_name']) ?></a>
                <?php if (!empty($sector['published'])): ?>
                    <span class="badge badge-live">Live</span>
                <?php else: ?>
                    <span class="badge badge-draft">Hidden</span>
                <?php endif; ?>
            </h3>
            <div class="client-thumbs">
                <?php foreach (array_slice($clients, 0, 8) as $c): ?>
                    <?php if (!empty($c['image'])): ?>
                        <img src="../<?= e($c['image']) ?>" alt="<?= e($c['name']) ?>" title="<?= e($c['name']) ?>">
                    <?php else: ?>
                        <span class="client-thumb-mono" title="<?= e($c['name']) ?> (no logo)"><?= e(client_initials($c['name'])) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (count($clients) > 8): ?><span class="client-thumb-more">+<?= count($clients) - 8 ?></span><?php endif; ?>
            </div>
            <div class="item-meta">
                <span><i class="ri-filter-3-line"></i>Filter label: <?= e($sector['short_name'] ?? '') ?></span>
                <span><i class="ri-building-line"></i><?= count($clients) ?> client<?= count($clients) === 1 ? '' : 's' ?></span>
                <?php if ($noLogo): ?><span><i class="ri-image-line"></i><?= $noLogo ?> without logo</span><?php endif; ?>
            </div>
        </div>
        <div class="item-actions">
            <a href="client-edit.php?slug=<?= e($sector['slug']) ?>" class="btn btn-sm"><i class="ri-pencil-line"></i> Edit</a>
            <?php if ($q === ''): ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($sector['slug']) ?>">
                <input type="hidden" name="action" value="up">
                <button class="btn btn-sm btn-secondary" title="Move up">↑</button>
            </form>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($sector['slug']) ?>">
                <input type="hidden" name="action" value="down">
                <button class="btn btn-sm btn-secondary" title="Move down">↓</button>
            </form>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($sector['slug']) ?>">
                <input type="hidden" name="action" value="toggle">
                <button class="btn btn-sm btn-secondary"><?= !empty($sector['published']) ? 'Hide' : 'Show' ?></button>
            </form>
            <form method="post" onsubmit="return confirm('Delete this sector and all its clients? Logo files are kept.');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($sector['slug']) ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-sm btn-danger-outline"><i class="ri-delete-bin-line"></i> Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<style>
    .client-thumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 10px 0 6px; }
    .client-thumbs img { width: 72px; height: 36px; object-fit: contain; background: #fff; border: 1px solid #eee; border-radius: 6px; padding: 3px; }
    .client-thumb-mono, .client-thumb-more { display: inline-flex; align-items: center; justify-content: center; height: 36px; min-width: 36px; padding: 0 8px; border-radius: 18px; font-size: 12px; font-weight: 700; }
    .client-thumb-mono { color: #e30016; background: rgba(227, 0, 22, .07); border: 1px dashed rgba(227, 0, 22, .35); }
    .client-thumb-more { color: #6b7280; background: #f3f4f6; }
</style>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
