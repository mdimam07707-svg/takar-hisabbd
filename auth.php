<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_MIN = 15;

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('hisab_admin');
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    start_session();
    $sent = (string) ($_POST['csrf'] ?? '');
    if ($sent === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(419);
        exit('সেশনের মেয়াদ শেষ। পেজ রিলোড করে আবার চেষ্টা করুন।');
    }
}

function login_blocked(): bool
{
    $pdo = db();
    $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL ' . LOGIN_WINDOW_MIN . ' MINUTE)');
    $st = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = :ip');
    $st->execute([':ip' => client_ip()]);
    return (int) $st->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}

function admin_login(string $username, string $password): bool
{
    $st = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1');
    $st->execute([':u' => $username]);
    $row = $st->fetch();

    // ইউজার না থাকলেও একটি ডামি হ্যাশ যাচাই করা হয় যাতে সময় সমান থাকে
    $dummy = '$2y$10$abcdefghijklmnopqrstuuK1b7Zq3eYb0yQ0m8n1p2r3s4t5u6v7w';
    $ok = $row ? password_verify($password, $row['password_hash']) : (password_verify($password, $dummy) && false);

    if (!$ok) {
        db()->prepare('INSERT INTO login_attempts (ip) VALUES (:ip)')->execute([':ip' => client_ip()]);
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $row['id'];
    $_SESSION['admin_name'] = $row['username'];
    db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = :id')->execute([':id' => $row['id']]);
    db()->prepare('DELETE FROM login_attempts WHERE ip = :ip')->execute([':ip' => client_ip()]);
    return true;
}

function require_admin(): void
{
    if (!is_installed()) {
        redirect('../install.php');
    }
    start_session();
    if (empty($_SESSION['admin_id'])) {
        redirect('login.php');
    }
}

function admin_logout(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}
