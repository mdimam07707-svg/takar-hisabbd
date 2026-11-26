<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// পাবলিক সেটিংস: অ্যাপের নাম, ঘোষণা, রক্ষণাবেক্ষণ মোড (গোপন কিছু নেই)
try {
    $s = all_settings();
} catch (Throwable $e) {
    $s = setting_defaults();
}

json_out([
    'app_name' => $s['app_name'],
    'announcement' => $s['announcement_active'] === '1' ? $s['announcement'] : '',
    'maintenance' => $s['maintenance'] === '1',
    'maintenance_message' => $s['maintenance_message'],
]);
