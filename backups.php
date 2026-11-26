<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

function backup_path(string $filename): ?string
{
    // শুধু আমাদের তৈরি করা ফাইলনাম গ্রহণযোগ্য (path traversal রোধ)
    if (!preg_match('/^backup_\d{8}_\d{6}_[0-9a-f]{6}\.json$/', $filename)) {
        return null;
    }
    $p = BACKUP_DIR . '/' . $filename;
    return is_file($p) ? $p : null;
}

if (isset($_GET['dl'])) {
    $st = db()->prepare('SELECT filename FROM backups WHERE id = :id');
    $st->execute([':id' => (int) $_GET['dl']]);
    $name = (string) $st->fetchColumn();
    $path = $name !== '' ? backup_path($name) : null;
    if (!$path) {
        http_response_code(404);
        exit('ফাইল পাওয়া যায়নি।');
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT filename FROM backups WHERE id = :id');
    $st->execute([':id' => $id]);
    $name = (string) $st->fetchColumn();
    if ($name !== '') {
        $path = backup_path($name);
        if ($path) {
            @unlink($path);
        }
        db()->prepare('DELETE FROM backups WHERE id = :id')->execute([':id' => $id]);
        flash('ok', 'ব্যাকআপ মুছে ফেলা হয়েছে।');
    }
    redirect('backups.php');
}

$rows = db()->query('SELECT id, label, size, ip, created_at FROM backups ORDER BY id DESC LIMIT 200')->fetchAll();
admin_header('ব্যাকআপ', 'backups');
?>
<div class="card">
<?php if (!$rows): ?>
  <p class="hint">কোনো ব্যাকআপ নেই।</p>
<?php else: ?>
  <div class="tbl"><table>
    <tr><th>#</th><th>লেবেল</th><th>আকার</th><th>IP</th><th>সময়</th><th></th></tr>
    <?php foreach ($rows as $b): ?>
      <tr>
        <td><?= (int) $b['id'] ?></td>
        <td><?= e($b['label'] ?: '—') ?></td>
        <td><?= e(format_bytes((int) $b['size'])) ?></td>
        <td class="mono"><?= e($b['ip']) ?></td>
        <td><?= e($b['created_at']) ?></td>
        <td>
          <div class="row">
            <a class="btn ghost sm" href="backups.php?dl=<?= (int) $b['id'] ?>">ডাউনলোড</a>
            <form method="post" onsubmit="return confirm('এই ব্যাকআপ স্থায়ীভাবে মুছে যাবে। নিশ্চিত?')">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
              <button class="btn danger sm" type="submit">মুছুন</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </table></div>
<?php endif; ?>
</div>
<?php admin_footer();
