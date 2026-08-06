<?php
require_once '../includes/config.php';
requireLogin('parent');
$db   = getDB();
$user = getCurrentUser();
$pid  = $user['id'];
$msg  = $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='save_tg') {
    $chat_id = trim($_POST['chat_id']??'');
    if ($chat_id) {
        $db->prepare("UPDATE users SET telegram_chat_id=? WHERE id=?")->execute([$chat_id, $pid]);
        sendTelegram($chat_id, "✅ ZiyoCRM\n\nSizning akkauntingiz muvaffaqiyatli bog'landi!\nEndi bildirishnomalar shu yerga keladi.");
        $msg = "Telegram muvaffaqiyatli ulandi!";
        $user = getCurrentUser();
    } else {
        $err = "Chat ID bo'sh bo'lmasin!";
    }
}
?><!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Profil — Ota-ona Panel</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">👤 Profilim</div>
      <div class="topbar-right">
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>
    <div class="page-content">
      <div style="max-width:600px;">

        <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

        <!-- Profile card -->
        <div class="card fade-in" style="margin-bottom:20px;">
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid rgba(240,246,252,0.08);">
            <div style="width:64px;height:64px;background:linear-gradient(135deg,#d97706,#b45309);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;">
              <?= strtoupper(substr($user['full_name'],0,1)) ?>
            </div>
            <div>
              <h2 style="font-size:20px;font-weight:800;"><?= htmlspecialchars($user['full_name']) ?></h2>
              <div style="font-size:13px;color:#8b949e;">@<?= htmlspecialchars($user['username']) ?></div>
              <span class="badge badge-warning" style="margin-top:4px;">👪 Ota-ona</span>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div style="background:rgba(255,255,255,0.03);border-radius:10px;padding:14px;">
              <div style="font-size:11px;color:#8b949e;margin-bottom:4px;">Foydalanuvchi nomi</div>
              <div style="font-weight:600;">@<?= htmlspecialchars($user['username']) ?></div>
            </div>
            <div style="background:rgba(255,255,255,0.03);border-radius:10px;padding:14px;">
              <div style="font-size:11px;color:#8b949e;margin-bottom:4px;">Telegram</div>
              <div style="font-weight:600;">
                <?= !empty($user['telegram_chat_id'])
                    ? '<span style="color:#6ee7b7">✅ Ulangan</span>'
                    : '<span style="color:#fca5a5">❌ Ulanmagan</span>' ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Telegram connect -->
        <div class="card fade-in">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">📱 Telegram Sozlama</h3>

          <div style="background:linear-gradient(135deg,rgba(37,99,235,0.1),rgba(99,102,241,0.07));border:1px solid rgba(37,99,235,0.25);border-radius:12px;padding:18px;margin-bottom:18px;">
            <h4 style="font-size:14px;font-weight:700;margin-bottom:10px;">📋 Qanday ulash kerak?</h4>
            <ol style="font-size:13px;color:#8b949e;line-height:2;padding-left:18px;">
              <li>Telegramda <code style="color:#a5b4fc;background:rgba(99,102,241,0.15);padding:1px 6px;border-radius:4px;">@userinfobot</code> botni oching</li>
              <li><code style="color:#a5b4fc;background:rgba(99,102,241,0.15);padding:1px 6px;border-radius:4px;">/start</code> buyrug'ini yuboring</li>
              <li>Bot sizga <strong>Chat ID</strong> raqamini beradi</li>
              <li>Shu raqamni quyidagi maydonga kiriting</li>
            </ol>
          </div>

          <form method="POST">
            <input type="hidden" name="action" value="save_tg">
            <div class="form-group">
              <label>Telegram Chat ID</label>
              <div class="iw">
                <span class="ii">📱</span>
                <input type="text" name="chat_id" placeholder="Masalan: 123456789"
                       value="<?= htmlspecialchars($user['telegram_chat_id']??'') ?>">
              </div>
            </div>
            <button type="submit" class="btn" style="background:linear-gradient(135deg,#d97706,#b45309);color:#fff;width:100%;padding:13px;font-size:14px;">
              📱 Telegram ulash
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
// === KUN / TUN REJIMI (THEME TOGGLE) ===
(function() {
  const savedTheme = localStorage.getItem('ziyo_theme') || 'dark';
  if (savedTheme === 'light') {
    document.documentElement.setAttribute('data-theme', 'light');
  }
})();

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const newTheme = current === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('ziyo_theme', newTheme);
  updateThemeButtons(newTheme);
}

function updateThemeButtons(theme) {
  document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
    btn.innerHTML = theme === 'light' ? '🌙 Tun rejimi' : '☀️ Kun rejimi';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const current = localStorage.getItem('ziyo_theme') || 'dark';
  updateThemeButtons(current);
});
</script>
</body>
</html>
