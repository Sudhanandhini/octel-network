<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/distributors.php';
require_once __DIR__ . '/../includes/solutions.php';
require_once __DIR__ . '/../includes/partners.php';
require_once __DIR__ . '/includes/search.php';
require_login();

$q = admin_search_query();
$searchFields = ['nav_name', 'slug', 'summary_bullets', 'intro_paragraphs'];
$distributors = admin_search_filter(load_distributors(), $q, $searchFields);
$solutions = admin_search_filter(load_solutions(), $q, $searchFields);
$partners = admin_search_filter(load_partners(), $q, $searchFields);
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/layout-top.php';

function render_content_table(array $items, string $type): void {
    if (!$items) {
        echo '<div class="empty-state">' . (admin_search_query() !== '' ? 'No matches in this section.' : 'Nothing here yet.') . '</div>';
        return;
    }
    ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $d): ?>
            <tr>
                <td><?= e($d['nav_name']) ?></td>
                <td>
                    <?php if (!empty($d['published'])): ?>
                        <span class="badge badge-live">Live</span>
                    <?php else: ?>
                        <span class="badge badge-draft">Draft</span>
                    <?php endif; ?>
                </td>
                <td><a class="link-edit" href="edit.php?type=<?= e($type) ?>&slug=<?= e($d['slug']) ?>">Edit &rarr;</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
?>
<div class="admin-card">
    <?php render_admin_search($q, [], 'Search distribution partners, solutions and partners by name…'); ?>
    <?php if ($q !== ''): ?>
        <p class="search-note" style="margin-bottom:0"><?= count($distributors) + count($solutions) + count($partners) ?> result(s) for “<?= e($q) ?>”</p>
    <?php endif; ?>
</div>

<div class="admin-card" id="distribution-partners">
    <div class="card-head">
        <div>
            <h1 class="page-title">Distribution Partners</h1>
            <p class="page-sub">Edit the content shown on distribution.html and each partner's detail page.</p>
        </div>
        <a href="edit.php?type=distributors" class="btn">+ Add New</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Changes saved.</div>
    <?php endif; ?>

    <?php render_content_table($distributors, 'distributors'); ?>
</div>

<div class="admin-card" id="solutions">
    <div class="card-head">
        <div>
            <h1 class="page-title">Solutions</h1>
            <p class="page-sub">Edit the content shown on services.html and each solution's detail page.</p>
        </div>
        <a href="edit.php?type=solutions" class="btn">+ Add New</a>
    </div>

    <?php render_content_table($solutions, 'solutions'); ?>
</div>

<div class="admin-card" id="our-partners">
    <div class="card-head">
        <div>
            <h1 class="page-title">Our Partners</h1>
            <p class="page-sub">Edit the content shown on partners.html and each product category's detail page.</p>
        </div>
        <a href="edit.php?type=partners" class="btn">+ Add New</a>
    </div>

    <?php render_content_table($partners, 'partners'); ?>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
