<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $id = (int) $_SESSION['admin_id'];
    $st = db()->prepare('SELECT password_hash FROM admins WHERE id = :id');
    $st->execute([':id' => $id]);
    $hash = (string) $st->fetchColumn();

    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    $username = trim((string) ($_POST['username'] ?? ''));

    if (!password_verify($current, $hash)) {
        flash('err', 'বর্তমান পাসওয়ার্ড ভুল।');
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        flash('err', 'ইউজারনেমে ৩–৫০টি ইংরেজি অক্ষর/সংখ্যা/_ . - ব্যবহার করুন।');
    } elseif ($new !== '' && strlen($new) < 8) {
        flash('err', 'নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।');
    } elseif ($new !== $confirm) {
        flash('err', 'নতুন পাসওয়ার্ড দুটি মিলছে না।');
    } else {
        try {
            if ($new !== '') {
                db()->prepare('UPDATE admins SET username = :u, password_hash = :h WHERE id = :id')
                    ->execute([':u' => $username, ':h' => password_hash($new, PASSWORD_DEFAULT), ':id' => $id]);
            } else {
                db()->prepare('UPDATE admins SET username = :u WHERE id = :id')
                    ->execute([':u' => $username, ':id' => $id]);
            }
            $_SESSION['admin_name'] = $username;
            session_regenerate_id(true);
            flash('ok', 'অ্যাকাউন্ট হালনাগাদ হয়েছে।');
        } catch (Throwable $e) {
            flash('err', 'এই ইউজারনেম আগে থেকেই আছে বা সংরক্ষণ করা যায়নি।');
        }
    }
    redirect('account.php');
}

admin_header('অ্যাকাউন্ট', 'account');
?>
<form method="post" class="card" autocomplete="off" style="max-width:480px">
  <?= csrf_field() ?>
  <label for="username">ইউজারনেম</label>
  <input type="text" id="username" name="username" required value="<?= e($_SESSION['admin_name'] ?? '') ?>">
  <label for="current">বর্তমান পাসওয়ার্ড (প্রয়োজনীয়)</label>
  <input type="password" id="current" name="current" required>
  <label for="new">নতুন পাসওয়ার্ড (বদলাতে না চাইলে ফাঁকা রাখুন)</label>
  <input type="password" id="new" name="new" minlength="8">
  <label for="confirm">নতুন পাসওয়ার্ড আবার লিখুন</label>
  <input type="password" id="confirm" name="confirm" minlength="8">
  <p><button class="btn" type="submit">হালনাগাদ করুন</button></p>
</form>
<?php admin_footer();
