<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/distributors.php';
require_once __DIR__ . '/../includes/solutions.php';
require_once __DIR__ . '/../includes/partners.php';
require_login();

$distributors = load_distributors();
$solutions = load_solutions();
$partners = load_partners();
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/layout-top.php';

function render_content_table(array $items, string $type): void {
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
