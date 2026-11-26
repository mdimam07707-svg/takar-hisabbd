<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$dbOk = false;
if (is_installed()) {
    try {
        db()->query('SELECT 1');
        $dbOk = true;
    } catch (Throwable $e) {
        $dbOk = false;
    }
}
json_out(['ok' => true, 'installed' => is_installed(), 'db' => $dbOk, 'version' => APP_VERSION]);
