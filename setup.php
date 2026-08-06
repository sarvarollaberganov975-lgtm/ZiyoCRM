<?php
// ============================================================
// ZiyoCRM — Telegram Bot Setup Skripti
// Webhookni o'rnatish va bot sozlamalarini tekshirish
// ============================================================

require_once __DIR__ . '/../includes/config.php';

$action  = $_GET['action'] ?? 'status';
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

// ─── WEBHOOK O'RNATISH ────────────────────────────────────
if ($action === 'set') {
    $webhookUrl = $baseUrl . '/bot/webhook.php';
    $res = file_get_contents(TELEGRAM_API_URL . 'setWebhook?url=' . urlencode($webhookUrl));
    $json = json_decode($res, true);
    echo json_encode(['action' => 'setWebhook', 'url' => $webhookUrl, 'result' => $json], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── WEBHOOK O'CHIRISH ────────────────────────────────────
if ($action === 'delete') {
    $res = file_get_contents(TELEGRAM_API_URL . 'deleteWebhook');
    echo json_encode(['action' => 'deleteWebhook', 'result' => json_decode($res, true)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── BOT MA'LUMOTLARI ─────────────────────────────────────
if ($action === 'me') {
    $res = file_get_contents(TELEGRAM_API_URL . 'getMe');
    echo json_encode(json_decode($res, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── BARCHA FOYDALANUVCHILAR TELEGRAM IDs ────────────────
if ($action === 'users') {
    $db   = getDB();
    $rows = $db->query("SELECT id, username, full_name, role, telegram_chat_id FROM users ORDER BY role, full_name")->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/html; charset=utf-8');
    echo '<style>body{font-family:sans-serif;background:#0d1117;color:#e6edf3;padding:20px}
    table{border-collapse:collapse;width:100%}
    th,td{padding:8px 12px;border:1px solid #30363d;text-align:left}
    th{background:#161b22} .linked{color:#3fb950} .not-linked{color:#f85149}
    .role-admin{color:#c084fc} .role-teacher{color:#60a5fa} .role-student{color:#34d399} .role-parent{color:#fbbf24}
    </style>';
    echo '<h2>👥 Foydalanuvchilar va Telegram holati</h2>';
    echo '<table><tr><th>#</th><th>Ism</th><th>Login</th><th>Rol</th><th>Telegram Chat ID</th><th>Holat</th></tr>';
    foreach ($rows as $i => $r) {
        $linked = !empty($r['telegram_chat_id']);
        $status = $linked ? '<span class="linked">✅ Ulangan</span>' : '<span class="not-linked">❌ Ulanmagan</span>';
        $chatId = $linked ? "<code>{$r['telegram_chat_id']}</code>" : '—';
        echo "<tr><td>".($i+1)."</td><td>{$r['full_name']}</td><td>{$r['username']}</td><td class=\"role-{$r['role']}\">{$r['role']}</td><td>{$chatId}</td><td>{$status}</td></tr>";
    }
    echo '</table>';
    exit;
}

// ─── TEST XABAR YUBORISH ──────────────────────────────────
if ($action === 'test' && isset($_GET['chat_id'])) {
    $chat_id = (int)$_GET['chat_id'];
    $msg = "🏫 <b>ZiyoCRM</b>\n\n✅ Test xabar muvaffaqiyatli yuborildi!\n🕐 " . date('Y-m-d H:i:s');
    $res = file_get_contents(TELEGRAM_API_URL . 'sendMessage?chat_id=' . $chat_id . '&text=' . urlencode($msg) . '&parse_mode=HTML');
    echo json_encode(json_decode($res, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── DEFAULT: STATUS SAHIFASI ────────────────────────────
$webhookRes  = @file_get_contents(TELEGRAM_API_URL . 'getWebhookInfo');
$webhookInfo = json_decode($webhookRes, true)['result'] ?? [];
$meRes       = @file_get_contents(TELEGRAM_API_URL . 'getMe');
$me          = json_decode($meRes, true)['result'] ?? [];
?><!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<title>Bot Setup — ZiyoCRM</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:sans-serif;background:#0d1117;color:#e6edf3;padding:30px;line-height:1.6}
h1{font-size:24px;margin-bottom:24px;color:#c084fc}
h2{font-size:16px;margin:20px 0 10px;color:#a5b4fc}
.card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:20px;margin-bottom:16px}
.row{display:flex;align-items:center;gap:12px;margin-bottom:8px}
.label{color:#8b949e;font-size:13px;min-width:140px}
.val{font-size:13px}
.ok{color:#3fb950} .err{color:#f85149} .warn{color:#d29922}
code{background:#0d1117;padding:2px 8px;border-radius:4px;font-size:12px;color:#79c0ff}
.btn{display:inline-block;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;margin:4px}
.btn-green{background:#238636;color:#fff} .btn-green:hover{background:#2ea043}
.btn-red{background:#da3633;color:#fff} .btn-red:hover{background:#f85149}
.btn-blue{background:#1f6feb;color:#fff} .btn-blue:hover{background:#388bfd}
.btn-gray{background:#30363d;color:#e6edf3} .btn-gray:hover{background:#3d444d}
pre{background:#0d1117;border:1px solid #30363d;padding:14px;border-radius:8px;overflow:auto;font-size:12px}
</style>
</head>
<body>

<h1>🤖 ZiyoCRM Bot Setup</h1>

<div class="card">
  <h2>📡 Bot ma'lumotlari</h2>
  <?php if ($me): ?>
  <div class="row"><span class="label">Bot nomi:</span><span class="val ok"><?= htmlspecialchars($me['first_name']) ?></span></div>
  <div class="row"><span class="label">Username:</span><span class="val"><code>@<?= $me['username'] ?></code></span></div>
  <div class="row"><span class="label">Bot ID:</span><span class="val"><code><?= $me['id'] ?></code></span></div>
  <div class="row"><span class="label">Token:</span><span class="val"><code><?= substr(TELEGRAM_BOT_TOKEN, 0, 15) ?>...</code></span></div>
  <?php else: ?>
  <div class="row"><span class="val err">❌ Bot bilan bog'lanib bo'lmadi. Token noto'g'ri.</span></div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>🔗 Webhook holati</h2>
  <?php
  $wUrl = $webhookInfo['url'] ?? '';
  $wOk  = !empty($wUrl);
  ?>
  <div class="row">
    <span class="label">Holat:</span>
    <span class="val <?= $wOk ? 'ok' : 'err' ?>"><?= $wOk ? '✅ O\'rnatilgan' : '❌ O\'rnatilmagan' ?></span>
  </div>
  <?php if ($wOk): ?>
  <div class="row"><span class="label">URL:</span><span class="val"><code><?= htmlspecialchars($wUrl) ?></code></span></div>
  <?php if (!empty($webhookInfo['last_error_message'])): ?>
  <div class="row"><span class="label">Oxirgi xato:</span><span class="val warn"><?= htmlspecialchars($webhookInfo['last_error_message']) ?></span></div>
  <?php endif; ?>
  <?php endif; ?>

  <div style="margin-top:14px">
    <a href="?action=set" class="btn btn-green">🔗 Webhook o'rnatish</a>
    <a href="?action=delete" class="btn btn-red">🗑️ Webhookni o'chirish</a>
    <a href="?action=me" class="btn btn-blue">🤖 Bot info (JSON)</a>
    <a href="?action=users" class="btn btn-gray">👥 Foydalanuvchilar</a>
  </div>

  <div style="margin-top:12px;font-size:12px;color:#8b949e">
    ⚠️ Webhook faqat HTTPS saytlarda ishlaydi. Lokal test uchun ngrok yoki similar tunnel ishlatilsin.
  </div>
</div>

<div class="card">
  <h2>📨 Test xabar yuborish</h2>
  <form method="get" action="">
    <input type="hidden" name="action" value="test">
    <div style="display:flex;gap:10px;align-items:center">
      <input type="number" name="chat_id" placeholder="Telegram Chat ID kiriting..." style="padding:8px 12px;background:#0d1117;border:1px solid #30363d;border-radius:8px;color:#e6edf3;font-size:13px;flex:1">
      <button type="submit" class="btn btn-green" style="border:none;cursor:pointer">📤 Yuborish</button>
    </div>
  </form>
</div>

<div class="card">
  <h2>📋 Webhook ma'lumotlari (raw)</h2>
  <pre><?= json_encode($webhookInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
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
