<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contacts.php';
require_login();

$id = (string)($_GET['id'] ?? '');
$c = get_contact($id);
if (!$c) {
    header('Location: contacts.php');
    exit;
}

$statuses = contact_statuses();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        if (delete_contact($id)) {
            header('Location: contacts.php?deleted=1');
            exit;
        }
        $error = 'Could not delete the enquiry.';
    } else {
        $newStatus = (string)($_POST['status'] ?? '');
        $items = load_items(CONTACTS_FILE);
        foreach ($items as $i => $row) {
            if (($row['id'] ?? '') !== $id) continue;
            if (isset($statuses[$newStatus])) $items[$i]['status'] = $newStatus;
            $items[$i]['notes'] = trim((string)($_POST['notes'] ?? ''));
            $items[$i]['updated_at'] = date('c');
        }
        if (save_contacts($items)) {
            header('Location: contact.php?id=' . urlencode($id) . '&saved=1');
            exit;
        }
        $error = 'Could not save. Check that data/contacts.json is writable.';
    }
}

$status = $c['status'] ?? 'new';
$csrf = csrf_token();
$pageTitle = 'Contact Enquiries';
$activeNav = 'contacts';
require __DIR__ . '/includes/layout-top.php';
?>
<p style="margin:0 0 16px"><a class="link-edit" href="contacts.php">&larr; Back to enquiries</a></p>

<div class="admin-card">
    <div class="card-head">
        <div>
            <h1 class="page-title"><?= e($c['name']) ?> <span class="badge badge-<?= e($status) ?>" style="vertical-align:middle"><?= e($statuses[$status] ?? $status) ?></span></h1>
            <p class="page-sub">Received on <?= e(format_contact_date($c['created_at'] ?? '')) ?></p>
        </div>
        <a href="mailto:<?= e($c['email']) ?>" class="btn"><i class="ri-mail-send-line"></i> Reply by Email</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Changes saved.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="detail-grid">
        <?php if (($c['organization'] ?? '') !== ''): ?>
        <div><div class="k">Organization</div><div class="v"><?= e($c['organization']) ?></div></div>
        <?php endif; ?>
        <div><div class="k">Email</div><div class="v"><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></div></div>
        <div><div class="k">Phone</div><div class="v"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $c['phone'])) ?>"><?= e($c['phone']) ?></a></div></div>
        <div><div class="k">Submitted From</div><div class="v">
            <?php if (!empty($c['page_url'])): ?>
                <a href="../<?= e(basename(strtok($c['page_url'], '?')) . (strpos($c['page_url'], '?') !== false ? strstr($c['page_url'], '?') : '')) ?>" target="_blank"><?= e($c['source'] ?? '') ?></a>
            <?php else: ?>
                <?= e($c['source'] ?? '—') ?>
            <?php endif; ?>
        </div></div>
        <div><div class="k">Service Interest</div><div class="v"><?= e(($c['interest'] ?? '') ?: '—') ?></div></div>
        <div><div class="k">Email Notification</div><div class="v">
            <?php if (($c['email_status'] ?? '') === 'sent'): ?>
                <span class="email-sent"><i class="ri-mail-check-line"></i> Sent to <?= e($c['email_to'] ?? '') ?></span>
            <?php elseif (($c['email_status'] ?? '') === 'failed'): ?>
                <span class="email-failed"><i class="ri-mail-close-line"></i> Not sent<?= !empty($c['email_to']) ? ' (to ' . e($c['email_to']) . ')' : '' ?>. Check the SMTP settings in .env</span>
            <?php else: ?>
                —
            <?php endif; ?>
        </div></div>
        <?php if (!empty($c['updated_at'])): ?>
            <div><div class="k">Last Updated</div><div class="v"><?= e(format_contact_date($c['updated_at'])) ?></div></div>
        <?php endif; ?>
    </div>

    <label>Message</label>
    <div class="cover-letter"><?= ($c['message'] ?? '') !== '' ? e($c['message']) : '<em>No message.</em>' ?></div>
</div>

<div class="admin-card">
    <h1 class="page-title">Follow-up</h1>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <label for="status">Status</label>
        <select id="status" name="status">
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="notes">Internal Notes <span class="hint">Only visible to admins.</span></label>
        <textarea id="notes" name="notes" rows="4"><?= e($c['notes'] ?? '') ?></textarea>

        <div class="form-actions">
            <button type="submit" class="btn">Save</button>
        </div>
    </form>

    <form method="post" onsubmit="return confirm('Delete this enquiry? This cannot be undone.');" style="margin-top:14px">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-sm btn-danger-outline"><i class="ri-delete-bin-line"></i> Delete Enquiry</button>
    </form>
</div>
<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
