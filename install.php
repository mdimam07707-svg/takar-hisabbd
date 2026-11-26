<?php
declare(strict_types=1);
// ইনস্টলার: একবার চালানোর পর এই ফাইলটি সার্ভার থেকে মুছে ফেলুন।
define('ROOT_PATH', __DIR__);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$errors = [];
$done = false;
$apiKey = '';

$checks = [
    'PHP ৭.৪ বা তার উপরে (বর্তমান: ' . PHP_VERSION . ')' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO MySQL এক্সটেনশন' => extension_loaded('pdo_mysql'),
    'mbstring এক্সটেনশন' => extension_loaded('mbstring'),
    'includes/ ফোল্ডারে লেখার অনুমতি' => is_writable(__DIR__ . '/includes'),
];
$envOk = !in_array(false, $checks, true);
$already = is_installed();

$v = ['host' => 'localhost', 'name' => '', 'user' => '', 'admin' => 'admin'];

if (!$already && $envOk && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $v['host'] = trim((string) ($_POST['host'] ?? ''));
    $v['name'] = trim((string) ($_POST['name'] ?? ''));
    $v['user'] = trim((string) ($_POST['user'] ?? ''));
    $pass = (string) ($_POST['pass'] ?? '');
    $v['admin'] = trim((string) ($_POST['admin'] ?? ''));
    $apass = (string) ($_POST['apass'] ?? '');

    if ($v['host'] === '' || $v['name'] === '' || $v['user'] === '') {
        $errors[] = 'ডাটাবেসের হোস্ট, নাম ও ইউজার দিন।';
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $v['admin'])) {
        $errors[] = 'অ্যাডমিন ইউজারনেমে ৩–৫০টি ইংরেজি অক্ষর/সংখ্যা/_ . - ব্যবহার করুন।';
    }
    if (strlen($apass) < 8) {
        $errors[] = 'অ্যাডমিন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।';
    }

    if (!$errors) {
        try {
            $pdo = new PDO(
                'mysql:host=' . $v['host'] . ';dbname=' . $v['name'] . ';charset=utf8mb4',
                $v['user'],
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $sql = (string) file_get_contents(__DIR__ . '/database/schema.sql');
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                $pdo->exec($stmt);
            }

            $count = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            if ($count > 0) {
                throw new RuntimeException('এই ডাটাবেসে আগে থেকেই অ্যাডমিন আছে। ফাঁকা ডাটাবেস ব্যবহার করুন।');
            }

            $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:u, :h)')
                ->execute([':u' => $v['admin'], ':h' => password_hash($apass, PASSWORD_DEFAULT)]);

            $apiKey = generate_api_key();
            $defaults = setting_defaults();
            $defaults['api_key'] = $apiKey;
            $ins = $pdo->prepare('INSERT INTO settings (k, v) VALUES (:k, :v) ON DUPLICATE KEY UPDATE v = VALUES(v)');
            foreach ($defaults as $k => $val) {
                $ins->execute([':k' => $k, ':v' => $val]);
            }

            $cfg = "<?php\nreturn " . var_export([
                'db' => ['host' => $v['host'], 'name' => $v['name'], 'user' => $v['user'], 'pass' => $pass],
            ], true) . ";\n";
            if (file_put_contents(__DIR__ . '/includes/config.php', $cfg, LOCK_EX) === false) {
                throw new RuntimeException('includes/config.php লেখা যায়নি। ফোল্ডারের অনুমতি দেখুন।');
            }
            if (!is_dir(BACKUP_DIR)) {
                @mkdir(BACKUP_DIR, 0755, true);
            }
            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'ইনস্টল ব্যর্থ: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>ইনস্টল — টাকার হিসাব</title>
<link rel="stylesheet" href="admin/assets/admin.css">
</head>
<body>
<div class="login" style="max-width:520px;margin:6vh auto">
  <div class="brand" style="font-size:24px;margin-bottom:18px">টাকার <span>হিসাব</span> · ইনস্টলার</div>

<?php if ($already && !$done): ?>
  <div class="card">
    <div class="flash ok">ইতিমধ্যে ইনস্টল করা আছে।</div>
    <p>নিরাপত্তার জন্য সার্ভার থেকে <code>install.php</code> ফাইলটি মুছে ফেলুন।</p>
    <p><a class="btn" href="admin/">অ্যাডমিন প্যানেলে যান</a></p>
  </div>

<?php elseif ($done): ?>
  <div class="card">
    <div class="flash ok">ইনস্টল সম্পন্ন হয়েছে।</div>
    <p><strong>এখনই করণীয়:</strong> ফাইল ম্যানেজার থেকে <code>install.php</code> মুছে ফেলুন।</p>
    <p class="hint">আপনার ব্যাকআপ API কী (পরে অ্যাডমিন &gt; সেটিংসেও পাবেন):</p>
    <p class="mono"><?= e($apiKey) ?></p>
    <p><a class="btn" href="admin/login.php">অ্যাডমিন লগইন</a></p>
  </div>

<?php else: ?>
  <div class="card" style="margin-bottom:16px">
    <h3>সার্ভার যাচাই</h3>
    <?php foreach ($checks as $label => $ok): ?>
      <div><?= $ok ? '✅' : '❌' ?> <?= e($label) ?></div>
    <?php endforeach; ?>
  </div>

  <?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>

  <?php if ($envOk): ?>
  <form method="post" class="card" autocomplete="off">
    <h3>ডাটাবেস (MySQL)</h3>
    <label>হোস্ট</label><input type="text" name="host" required value="<?= e($v['host']) ?>">
    <label>ডাটাবেসের নাম</label><input type="text" name="name" required value="<?= e($v['name']) ?>">
    <label>ডাটাবেস ইউজার</label><input type="text" name="user" required value="<?= e($v['user']) ?>">
    <label>ডাটাবেস পাসওয়ার্ড</label><input type="password" name="pass">
    <h3 style="margin-top:22px">অ্যাডমিন অ্যাকাউন্ট</h3>
    <label>ইউজারনেম</label><input type="text" name="admin" required value="<?= e($v['admin']) ?>">
    <label>পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</label><input type="password" name="apass" required minlength="8">
    <p><button class="btn" type="submit">ইনস্টল করুন</button></p>
    <p class="hint">হোস্টিং কন্ট্রোল প্যানেলের MySQL Databases অংশ থেকে ডাটাবেসের তথ্য পাবেন (InfinityFree-এ হোস্ট সাধারণত <code>sqlXXX.infinityfree.com</code> ধরনের হয়, <code>localhost</code> নয়)।</p>
  </form>
  <?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
