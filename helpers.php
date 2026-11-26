<?php
declare(strict_types=1);

function config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $file = __DIR__ . '/config.php';
        $cfg = is_file($file) ? (array) require $file : [];
    }
    return $cfg;
}

function is_installed(): bool
{
    $c = config();
    return !empty($c['db']['name']);
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url)
{
    header('Location: ' . $url);
    exit;
}

function json_out(array $data, int $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 2) . ' MB';
}

function setting_defaults(): array
{
    return [
        'app_name' => 'টাকার হিসাব',
        'announcement' => '',
        'announcement_active' => '0',
        'maintenance' => '0',
        'maintenance_message' => 'সাইটটি এখন রক্ষণাবেক্ষণের কাজে আছে। একটু পরে আবার চেষ্টা করুন।',
        'api_key' => '',
    ];
}

function all_settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = setting_defaults();
    if (is_installed()) {
        foreach (db()->query('SELECT k, v FROM settings') as $row) {
            $cache[$row['k']] = (string) $row['v'];
        }
    }
    return $cache;
}

function get_setting(string $key, string $default = ''): string
{
    $s = all_settings();
    return $s[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $st = db()->prepare(
        'INSERT INTO settings (k, v) VALUES (:k, :v) ON DUPLICATE KEY UPDATE v = VALUES(v)'
    );
    $st->execute([':k' => $key, ':v' => $value]);
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function take_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function generate_api_key(): string
{
    return bin2hex(random_bytes(24));
}
