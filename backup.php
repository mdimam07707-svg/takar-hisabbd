<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// POST /api/backup.php   Header: X-API-Key: <অ্যাডমিন > সেটিংস থেকে নেওয়া কী>
// Body: JSON (সর্বোচ্চ ২ MB)   Optional header: X-Backup-Label
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    json_out(['ok' => false, 'error' => 'Method not allowed'], 405);
}
if (!is_installed()) {
    json_out(['ok' => false, 'error' => 'Not installed'], 503);
}

try {
    $expected = get_setting('api_key');
    $sent = (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');
    if ($expected === '' || !hash_equals($expected, $sent)) {
        json_out(['ok' => false, 'error' => 'Unauthorized'], 401);
    }

    $max = 2 * 1024 * 1024;
    $raw = (string) file_get_contents('php://input', false, null, 0, $max + 1);
    if ($raw === '' || strlen($raw) > $max) {
        json_out(['ok' => false, 'error' => 'Body empty or too large (max 2MB)'], 413);
    }
    json_decode($raw);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_out(['ok' => false, 'error' => 'Invalid JSON'], 400);
    }

    // সহজ রেট লিমিট: ঘণ্টায় সর্বোচ্চ ৬০টি
    $recent = (int) db()->query('SELECT COUNT(*) FROM backups WHERE created_at > (NOW() - INTERVAL 1 HOUR)')->fetchColumn();
    if ($recent >= 60) {
        json_out(['ok' => false, 'error' => 'Rate limit exceeded'], 429);
    }

    if (!is_dir(BACKUP_DIR) && !mkdir(BACKUP_DIR, 0755, true)) {
        json_out(['ok' => false, 'error' => 'Storage unavailable'], 500);
    }

    $filename = 'backup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.json';
    if (file_put_contents(BACKUP_DIR . '/' . $filename, $raw, LOCK_EX) === false) {
        json_out(['ok' => false, 'error' => 'Write failed'], 500);
    }

    $label = mb_substr(trim((string) ($_SERVER['HTTP_X_BACKUP_LABEL'] ?? '')), 0, 80);
    $st = db()->prepare('INSERT INTO backups (label, filename, size, ip) VALUES (:l, :f, :s, :ip)');
    $st->execute([':l' => $label, ':f' => $filename, ':s' => strlen($raw), ':ip' => client_ip()]);

    json_out(['ok' => true, 'id' => (int) db()->lastInsertId(), 'size' => strlen($raw)], 201);
} catch (Throwable $e) {
    json_out(['ok' => false, 'error' => 'Server error'], 500);
}
