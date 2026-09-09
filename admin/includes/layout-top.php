<?php
/**
 * Shared admin chrome. Expects $pageTitle to be set before including.
 * Optionally set $activeNav to one of: dashboard, distributors, solutions, partners, password.
 * Requires ../../includes/auth.php to already be loaded (for e(), is_logged_in()).
 */
$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? 'dashboard';
$navItems = [
    'dashboard'   => ['label' => 'Dashboard',            'href' => 'dashboard.php',                          'icon' => 'ri-dashboard-line'],
    'distributors'=> ['label' => 'Distribution Partners', 'href' => 'dashboard.php#distribution-partners',    'icon' => 'ri-truck-line'],
    'solutions'   => ['label' => 'Solutions',             'href' => 'dashboard.php#solutions',                'icon' => 'ri-stack-line'],
    'partners'    => ['label' => 'Our Partners',          'href' => 'dashboard.php#our-partners',             'icon' => 'ri-team-line'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> — Octel Admin</title>
    <link rel="stylesheet" href="../assets/css/remixicon.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap");

        :root {
            color-scheme: light;
            --primaryFont: "DM Sans", sans-serif;
            --secondaryFont: "Rethink Sans", sans-serif;
            --primaryColor: #00252C;
            --accentColor: #e30016;
            --titleColor: #14161a;
            --paraColor: #5b6168;
            --sidebarWidth: 264px;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: var(--primaryFont);
            font-size: 16px;
            background: #f3f4f6;
            color: var(--titleColor);
            line-height: 1.5;
        }
        h1, h2, h3, .sidebar-brand { font-family: var(--secondaryFont); }

        .admin-shell {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebarWidth);
            flex-shrink: 0;
            background: var(--primaryColor);
            color: rgba(255,255,255,.85);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            padding: 28px 18px;
        }
        .sidebar-brand {
            font-size: 21px;
            font-weight: 700;
            color: #fff;
            margin: 0 8px 2px;
        }
        .sidebar-tag {
            font-size: 13px;
            color: rgba(255,255,255,.55);
            margin: 0 8px 32px;
        }
        .sidebar-nav { flex: 1; }
        .sidebar-nav .nav-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: rgba(255,255,255,.4);
            margin: 22px 12px 10px;
        }
        .sidebar-nav .nav-label:first-child { margin-top: 0; }
        .sidebar-nav a, .sidebar-foot a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 12px;
            border-radius: 10px;
            color: rgba(255,255,255,.78);
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 4px;
            transition: background .15s ease, color .15s ease;
        }
        .sidebar-nav a i, .sidebar-foot a i { font-size: 18px; line-height: 1; }
        .sidebar-nav a:hover, .sidebar-foot a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar-nav a.active, .sidebar-foot a.active { background: var(--accentColor); color: #fff; }
        .sidebar-foot {
            border-top: 1px solid rgba(255,255,255,.12);
            padding-top: 14px;
            margin-top: 14px;
        }
        .sidebar-foot a { font-size: 14px; color: rgba(255,255,255,.6); }
        .sidebar-foot a:last-child { margin-bottom: 0; }

        /* Main area */
        .admin-main { flex: 1; min-width: 0; }
        .admin-header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 20px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .admin-header h1 {
            font-size: 24px;
            margin: 0;
            font-weight: 700;
        }
        .admin-wrap {
            max-width: 1040px;
            margin: 0 auto;
            padding: 32px 36px 70px;
        }
        .admin-card {
            background: #fff;
            border-radius: 14px;
            padding: 32px 34px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
            margin-bottom: 26px;
        }
        .card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        h1.page-title {
            font-size: 24px;
            margin: 0 0 8px;
        }
        p.page-sub {
            color: var(--paraColor);
            font-size: 15px;
            margin: 0 0 22px;
        }
        table.admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }
        table.admin-table th, table.admin-table td {
            text-align: left;
            padding: 14px 10px;
            border-bottom: 1px solid #eef0f2;
        }
        table.admin-table th { color: var(--paraColor); font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-live { background: #dcfce7; color: #166534; }
        .badge-draft { background: #fef3c7; color: #92400e; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 9px;
            background: var(--accentColor);
            color: #fff !important;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            border: 0;
            cursor: pointer;
        }
        .btn:hover { background: #c40013; }
        .btn-secondary { background: #e5e7eb; color: var(--titleColor) !important; }
        .btn-secondary:hover { background: #d1d5db; }
        .link-edit { color: var(--accentColor); text-decoration: none; font-weight: 700; font-size: 15px; }
        .link-edit:hover { text-decoration: underline; }
        .alert { padding: 12px 16px; border-radius: 9px; font-size: 15px; margin-bottom: 22px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        label { display: block; font-weight: 700; font-size: 15px; margin: 18px 0 7px; }
        label .hint { display: block; font-weight: 400; color: var(--paraColor); font-size: 13px; margin-top: 3px; }
        input[type=text], input[type=password], input[type=file], textarea {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            font-size: 15px;
            font-family: inherit;
        }
        textarea { resize: vertical; }
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--accentColor);
            box-shadow: 0 0 0 3px rgba(227,0,22,.10);
        }
        .checkbox-row { display: flex; align-items: center; gap: 9px; margin: 18px 0; }
        .checkbox-row input { width: auto; }
        .form-actions { margin-top: 28px; display: flex; gap: 12px; }
        .current-image { max-width: 240px; border-radius: 9px; margin-top: 10px; display: block; }

        /* Block editor */
        .blocks-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
        .small-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: var(--titleColor);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        .small-btn:hover { background: #f3f4f6; border-color: #b9bfc7; }
        .blocks-editor { margin-top: 16px; }
        .blocks-empty {
            color: var(--paraColor);
            font-size: 14px;
            padding: 18px;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            text-align: center;
            margin: 0;
        }
        .block-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 14px;
            overflow: hidden;
            background: #fafafa;
        }
        .block-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 14px;
            background: #f0f1f3;
            border-bottom: 1px solid #e5e7eb;
        }
        .block-type-tag {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--paraColor);
        }
        .block-controls { display: flex; gap: 4px; }
        .icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: var(--titleColor);
            cursor: pointer;
            font-size: 14px;
        }
        .icon-btn:hover { background: #f3f4f6; }
        .block-card-body { padding: 14px 16px; background: #fff; }
        .block-field { margin-bottom: 12px; }
        .block-field:last-child { margin-bottom: 0; }
        .block-field label { font-size: 12px; font-weight: 600; color: var(--paraColor); margin: 0 0 5px; text-transform: none; }
        .block-field input[type=text], .block-field textarea, .block-field select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 14px;
            font-family: inherit;
        }
        .block-row-columns { display: grid; gap: 14px; }
        .block-column {
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            padding: 10px;
        }
        .block-column-head {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--paraColor);
            margin-bottom: 8px;
        }
        .block-mini-toolbar { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .block-mini-toolbar .small-btn { padding: 6px 10px; font-size: 12px; }

        @media (max-width: 860px) {
            .admin-shell { flex-direction: column; }
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: static;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
                padding: 16px 20px;
            }
            .sidebar-brand, .sidebar-tag { margin: 0 16px 0 0; }
            .sidebar-tag { display: none; }
            .sidebar-nav { flex: 1; display: flex; flex-wrap: wrap; }
            .sidebar-nav .nav-label { display: none; }
            .sidebar-nav a { margin: 0 4px 0 0; padding: 9px 12px; font-size: 14px; }
            .sidebar-foot { border-top: 0; margin: 0; padding: 0; display: flex; }
            .admin-wrap { padding: 24px 18px 50px; }
            .admin-header { padding: 16px 18px; }
            .admin-card { padding: 22px 20px; }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="sidebar-brand">Octel Networks</div>
            <div class="sidebar-tag">Admin Panel</div>
            <nav class="sidebar-nav">
                <div class="nav-label">Content</div>
                <?php foreach ($navItems as $key => $item): ?>
                    <a href="<?= e($item['href']) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>">
                        <i class="<?= e($item['icon']) ?>"></i><?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-foot">
                <a href="../distribution.html" target="_blank"><i class="ri-external-link-line"></i>View Site</a>
                <a href="change-password.php" class="<?= $activeNav === 'password' ? 'active' : '' ?>"><i class="ri-lock-password-line"></i>Change Password</a>
                <a href="logout.php"><i class="ri-logout-box-r-line"></i>Log Out</a>
            </div>
        </aside>
        <div class="admin-main">
            <header class="admin-header">
                <h1><?= e($pageTitle) ?></h1>
            </header>
            <div class="admin-wrap">
