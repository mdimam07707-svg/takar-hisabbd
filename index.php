<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

$pdo = db();
$row = $pdo->query('SELECT COUNT(*) AS n, COALESCE(SUM(size),0) AS total, MAX(created_at) AS last FROM backups')->fetch();
$recent = $pdo->query('SELECT id, label, size, created_at FROM backups ORDER BY id DESC LIMIT 5')->fetchAll();
$maint = get_setting('maintenance') === '1';
$ann = get_setting('announcement_active') === '1' && get_setting('announcement') !== '';
$appBuilt = is_file(ROOT_PATH . '/index.html')
    && strpos((string) file_get_contents(ROOT_PATH . '/index.html'), 'id="root"') !== false;

admin_header('ড্যাশবোর্ড', 'index');
?>
<div class="grid">
  <div class="card"><h3>মোট ব্যাকআপ</h3><div class="big"><?= (int) $row['n'] ?></div></div>
  <div class="card"><h3>ব্যাকআপের আকার</h3><div class="big"><?= e(format_bytes((int) $row['total'])) ?></div></div>
  <div class="card"><h3>সর্বশেষ ব্যাকআপ</h3><div class="big" style="font-size:16px"><?= $row['last'] ? e($row['last']) : '—' ?></div></div>
  <div class="card"><h3>রক্ষণাবেক্ষণ মোড</h3><div class="big"><span class="badge <?= $maint ? 'warn' : 'on' ?>"><?= $maint ? 'চালু' : 'বন্ধ' ?></span></div></div>
  <div class="card"><h3>ঘোষণা ব্যানার</h3><div class="big"><span class="badge <?= $ann ? 'on' : '' ?>"><?= $ann ? 'সক্রিয়' : 'নিষ্ক্রিয়' ?></span></div></div>
  <div class="card"><h3>অ্যাপ বিল্ড</h3><div class="big"><span class="badge <?= $appBuilt ? 'on' : 'warn' ?>"><?= $appBuilt ? 'আপলোড হয়েছে' : 'বাকি' ?></span></div></div>
</div>

<div class="card">
  <h3>সাম্প্রতিক ব্যাকআপ</h3>
  <?php if (!$recent): ?>
    <p class="hint">এখনও কোনো ব্যাকআপ আসেনি। API কী সেটিংস পেজে পাবেন।</p>
  <?php else: ?>
    <div class="tbl"><table>
      <tr><th>#</th><th>লেবেল</th><th>আকার</th><th>সময়</th></tr>
      <?php foreach ($recent as $b): ?>
        <tr><td><?= (int) $b['id'] ?></td><td><?= e($b['label'] ?: '—') ?></td><td><?= e(format_bytes((int) $b['size'])) ?></td><td><?= e($b['created_at']) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
    <p><a href="backups.php">সব ব্যাকআপ দেখুন →</a></p>
  <?php endif; ?>
</div>

<p class="hint">PHP <?= e(PHP_VERSION) ?> · প্যানেল সংস্করণ <?= e(APP_VERSION) ?></p>
<?php admin_footer();
