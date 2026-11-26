<?php
declare(strict_types=1);

function admin_header(string $title, string $active): void
{
    $nav = [
        'index' => ['index.php', 'ড্যাশবোর্ড'],
        'settings' => ['settings.php', 'সেটিংস'],
        'backups' => ['backups.php', 'ব্যাকআপ'],
        'account' => ['account.php', 'অ্যাকাউন্ট'],
    ];
    header('Content-Type: text/html; charset=utf-8');
    header('X-Frame-Options: DENY');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    ?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> — অ্যাডমিন</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="wrap">
  <aside class="side">
    <div class="brand">টাকার <span>হিসাব</span></div>
    <?php foreach ($nav as $key => $item): ?>
      <a class="nav<?= $key === $active ? ' on' : '' ?>" href="<?= e($item[0]) ?>"><?= e($item[1]) ?></a>
    <?php endforeach; ?>
    <a class="nav" href="../" target="_blank" rel="noopener">সাইট দেখুন ↗</a>
    <form method="post" action="logout.php">
      <?= csrf_field() ?>
      <button class="btn ghost sm" type="submit">লগআউট (<?= e($_SESSION['admin_name'] ?? '') ?>)</button>
    </form>
  </aside>
  <main class="main">
    <h1><?= e($title) ?></h1>
    <?php if ($f = take_flash()): ?>
      <div class="flash <?= $f['type'] === 'ok' ? 'ok' : 'err' ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
</body>
</html>
<?php
}
