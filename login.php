<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (!is_installed()) {
    redirect('../install.php');
}
start_session();
if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    try {
        if (login_blocked()) {
            $error = 'অনেকবার ভুল চেষ্টা হয়েছে। ' . LOGIN_WINDOW_MIN . ' মিনিট পর আবার চেষ্টা করুন।';
        } elseif (admin_login(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
            redirect('index.php');
        } else {
            $error = 'ইউজারনেম বা পাসওয়ার্ড ভুল।';
        }
    } catch (Throwable $e) {
        $error = 'ডাটাবেস সংযোগে সমস্যা হয়েছে।';
    }
}
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>লগইন — অ্যাডমিন</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="login">
  <div class="brand" style="font-size:24px;margin-bottom:20px">টাকার <span>হিসাব</span> · অ্যাডমিন</div>
  <div class="card">
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="u">ইউজারনেম</label>
      <input type="text" id="u" name="username" required autofocus>
      <label for="p">পাসওয়ার্ড</label>
      <input type="password" id="p" name="password" required>
      <p><button class="btn" type="submit" style="width:100%">লগইন</button></p>
    </form>
  </div>
</div>
</body>
</html>
