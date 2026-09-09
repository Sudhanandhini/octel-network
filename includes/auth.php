<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ADMIN_USERS_FILE', __DIR__ . '/../data/admin-users.json');

function load_admin_users(): array {
    if (!file_exists(ADMIN_USERS_FILE)) return [];
    $data = json_decode(file_get_contents(ADMIN_USERS_FILE), true);
    return is_array($data) ? $data : [];
}

function save_admin_users(array $users): bool {
    return file_put_contents(ADMIN_USERS_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function verify_admin_login(string $username, string $password): bool {
    foreach (load_admin_users() as $user) {
        if (hash_equals((string)($user['username'] ?? ''), $username)
            && password_verify($password, (string)($user['password_hash'] ?? ''))) {
            return true;
        }
    }
    return false;
}

function update_admin_password(string $username, string $newPassword): bool {
    $users = load_admin_users();
    $found = false;
    foreach ($users as &$user) {
        if (hash_equals((string)($user['username'] ?? ''), $username)) {
            $user['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $found = true;
        }
    }
    unset($user);
    return $found && save_admin_users($users);
}

function is_logged_in(): bool {
    return !empty($_SESSION['admin_username']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool {
    return !empty($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Very small brute-force throttle keyed on session. */
function login_throttle_wait(): void {
    $attempts = $_SESSION['login_attempts'] ?? 0;
    if ($attempts >= 3) {
        usleep(min($attempts * 400000, 3000000));
    }
}

function login_register_failure(): void {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
}

function login_reset_failures(): void {
    unset($_SESSION['login_attempts']);
}

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
