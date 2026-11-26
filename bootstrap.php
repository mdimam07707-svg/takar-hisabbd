<?php
declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('APP_VERSION', '1.0.0');
define('BACKUP_DIR', ROOT_PATH . '/uploads/backups');

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Dhaka');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
