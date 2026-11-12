<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contacts.php';
require_once __DIR__ . '/includes/search.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (delete_contact((string)($_POST['id'] ?? ''))) {
        header('Location: contacts.php?deleted=1');
        exit;
    } else {
        $error = 'Could not delete the enquiry.';
    }
}

$statuses = contact_statuses();
$total = load_contacts();
$q = admin_search_query();
$all = admin_search_filter($total, $q, ['name', 'organization', 'email', 'phone', 'message', 'source', 'interest', 'notes']);
$counts = count_contacts_by_status($all);

$statusFilter = (string)($_GET['status'] ?? '');
if (!isset($statuses[$statusFilter])) $statusFilter = '';
$items = $statusFilter === '' ? $all : array_values(array_filter($all, function ($c) use ($statusFilter) {
    return ($c['status'] ?? 'new') === $statusFilter;
}));

$csrf = csrf_token();
$pageTitle = 'Contact Enquiries';
$activeNav = 'contacts';
require __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <div class="card-head">
        <div>
            <h1 class="page-title">Contact Enquiries</h1>
            <p class="page-sub">Messages from the Contact page and the enquiry form on each solution / partner page. Total: <?= count($total) ?></p>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Enquiry deleted.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php render_admin_search($q, ['status' => $statusFilter], 'Search by name, organization, email, phone, message or page…'); ?>
    <?php if ($q !== ''): ?>
        <p class="search-note"><?= count($all) ?> result<?= count($all) === 1 ? '' : 's' ?> for “<?= e($q) ?>”</p>
    <?php endif; ?>

    <div class="filter-tabs">
        <a href="contacts.php<?= $q !== '' ? '?' . e(http_build_query(['q' => $q])) : '' ?>" class="<?= $statusFilter === '' ? 'active' : '' ?>">All (<?= count($all) ?>)</a>
        <?php foreach ($statuses as $key => $label): ?>
            <a href="contacts.php?<?= e(http_build_query(array_filter(['status' => $key, 'q' => $q], 'strlen'))) ?>" class="<?= $statusFilter === $key ? 'active' : '' ?>"><?= e($label) ?> (<?= $counts[$key] ?>)</a>
        <?php endforeach; ?>
    </div>

    <?php if (!$items): ?>
        <div class="empty-state">No enquiries found.</div>
    <?php endif; ?>

    <?php foreach ($items as $c): $status = $c['status'] ?? 'new'; ?>
    <div class="list-item">
        <div style="flex:1;min-width:260px">
            <h3><a href="contact.php?id=<?= e($c['id']) ?>"><?= e($c['name']) ?></a></h3>
            <p class="item-sub">
                From: <?= e($c['source'] ?? '') ?><?php if (!empty($c['interest'])): ?> &middot; Interest: <?= e($c['interest']) ?><?php endif; ?>
            </p>
            <?php if (!empty($c['message'])): ?>
                <p class="item-sub" style="color:var(--titleColor)"><?= e(mb_strimwidth($c['message'], 0, 160, '…')) ?></p>
            <?php endif; ?>
            <div class="item-meta">
                <?php if (($c['organization'] ?? '') !== ''): ?><span><i class="ri-building-line"></i><?= e($c['organization']) ?></span><?php endif; ?>
                <span><i class="ri-mail-line"></i><?= e($c['email']) ?></span>
                <span><i class="ri-phone-line"></i><?= e($c['phone']) ?></span>
                <span><i class="ri-calendar-line"></i><?= e(format_contact_date($c['created_at'] ?? '')) ?></span>
                <?php if (($c['email_status'] ?? '') === 'sent'): ?>
                    <span class="email-sent" title="Notification emailed to <?= e($c['email_to'] ?? '') ?>"><i class="ri-mail-check-line"></i>Emailed to <?= e($c['email_to'] ?? '') ?></span>
                <?php elseif (($c['email_status'] ?? '') === 'failed'): ?>
                    <span class="email-failed" title="Check the SMTP settings in .env"><i class="ri-mail-close-line"></i>Email not sent</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="item-actions">
            <span class="badge badge-<?= e($status) ?>"><?= e($statuses[$status] ?? $status) ?></span>
            <a href="contact.php?id=<?= e($c['id']) ?>" class="btn btn-sm"><i class="ri-eye-line"></i> View</a>
            <form method="post" onsubmit="return confirm('Delete this enquiry? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                <button class="btn btn-sm btn-danger-outline" title="Delete"><i class="ri-delete-bin-line"></i></button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
