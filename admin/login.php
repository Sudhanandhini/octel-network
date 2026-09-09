<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    login_throttle_wait();

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (verify_admin_login($username, $password)) {
            login_reset_failures();
            session_regenerate_id(true);
            $_SESSION['admin_username'] = $username;
            header('Location: dashboard.php');
            exit;
        }

        login_register_failure();
        $error = 'Incorrect username or password.';
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — Octel Networks</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f1115;
            font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 360px;
            background: #ffffff;
            border-radius: 14px;
            padding: 36px 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        }
        .login-card h1 {
            font-size: 20px;
            margin: 0 0 4px;
            color: #14161a;
        }
        .login-card p.sub {
            margin: 0 0 24px;
            color: #6b7280;
            font-size: 13px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        input[type=text], input[type=password] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 18px;
        }
        input[type=text]:focus, input[type=password]:focus {
            outline: none;
            border-color: #e30016;
            box-shadow: 0 0 0 3px rgba(227,0,22,.12);
        }
        button {
            width: 100%;
            padding: 11px;
            border: 0;
            border-radius: 8px;
            background: #e30016;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }
        button:hover { background: #c40013; }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Octel Networks Admin</h1>
        <p class="sub">Sign in to edit distribution partner pages.</p>
        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Log In</button>
        </form>
    </div>
</body>
</html>
