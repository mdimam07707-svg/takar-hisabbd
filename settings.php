<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'regen') {
        set_setting('api_key', generate_api_key());
        flash('ok', 'নতুন API কী তৈরি হয়েছে। পুরনো কী আর কাজ করবে না।');
    } else {
        $name = mb_substr(trim((string) ($_POST['app_name'] ?? '')), 0, 60);
        set_setting('app_name', $name !== '' ? $name : 'টাকার হিসাব');
        set_setting('announcement', mb_substr(trim((string) ($_POST['announcement'] ?? '')), 0, 300));
        set_setting('announcement_active', isset($_POST['announcement_active']) ? '1' : '0');
        set_setting('maintenance', isset($_POST['maintenance']) ? '1' : '0');
        set_setting('maintenance_message', mb_substr(trim((string) ($_POST['maintenance_message'] ?? '')), 0, 300));
        flash('ok', 'সেটিংস সংরক্ষণ হয়েছে।');
    }
    redirect('settings.php');
}

$s = all_settings();
admin_header('সেটিংস', 'settings');
?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">

  <label for="app_name">অ্যাপের নাম</label>
  <input type="text" id="app_name" name="app_name" maxlength="60" value="<?= e($s['app_name']) ?>">

  <label for="announcement">ঘোষণা (সাইটের উপরে ব্যানার হিসেবে দেখাবে)</label>
  <textarea id="announcement" name="announcement" maxlength="300"><?= e($s['announcement']) ?></textarea>
  <div class="check"><input type="checkbox" id="aa" name="announcement_active" value="1" <?= $s['announcement_active'] === '1' ? 'checked' : '' ?>><label for="aa" style="margin:0;color:inherit">ঘোষণা ব্যানার চালু</label></div>

  <label for="mm">রক্ষণাবেক্ষণ বার্তা</label>
  <textarea id="mm" name="maintenance_message" maxlength="300"><?= e($s['maintenance_message']) ?></textarea>
  <div class="check"><input type="checkbox" id="mt" name="maintenance" value="1" <?= $s['maintenance'] === '1' ? 'checked' : '' ?>><label for="mt" style="margin:0;color:inherit">রক্ষণাবেক্ষণ মোড চালু (ভিজিটররা অ্যাপের বদলে বার্তা দেখবে)</label></div>

  <button class="btn" type="submit">সংরক্ষণ করুন</button>
</form>

<div class="card" style="margin-top:18px">
  <h3>ব্যাকআপ API কী</h3>
  <p class="mono"><?= e($s['api_key']) ?></p>
  <p class="hint">এন্ডপয়েন্ট: <code>POST /api/backup.php</code> · হেডার <code>X-API-Key</code> · বডি: JSON (সর্বোচ্চ ২ MB)। কী কারও সাথে শেয়ার করবেন না।</p>
  <form method="post" onsubmit="return confirm('নতুন কী তৈরি করলে পুরনোটি অচল হয়ে যাবে। চালিয়ে যাবেন?')">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="regen">
    <button class="btn ghost sm" type="submit">নতুন কী তৈরি করুন</button>
  </form>
</div>
<?php admin_footer();
