<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

$msg = $err = '';
$bot_token    = TELEGRAM_BOT_TOKEN;
$bot_username = defined('TELEGRAM_BOT_USERNAME') ? TELEGRAM_BOT_USERNAME : 'ziyo_crm_bot';
$is_configured = (strlen($bot_token) > 20 && strpos($bot_token, ':') !== false);

// Bot haqida ma'lumot (getMe)
$me = [];
if ($is_configured) {
    $meRes = @file_get_contents(TELEGRAM_API_URL . 'getMe');
    $me = json_decode($meRes, true)['result'] ?? [];
}

// Webhook holati
$webhookInfo = [];
if ($is_configured) {
    $whRes = @file_get_contents(TELEGRAM_API_URL . 'getWebhookInfo');
    $webhookInfo = json_decode($whRes, true)['result'] ?? [];
}

// Token saqlash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_token') {
        // config.php ni yangilash
        $new_token = trim($_POST['bot_token'] ?? '');
        if ($new_token) {
            $config_file = __DIR__ . '/../includes/config.php';
            $content = file_get_contents($config_file);
            $content = preg_replace(
                "/define\('TELEGRAM_BOT_TOKEN', '[^']*'\)/",
                "define('TELEGRAM_BOT_TOKEN', '$new_token')",
                $content
            );
            file_put_contents($config_file, $content);
            $msg = "✅ Bot token saqlandi! Sahifani yangilang.";
        } else {
            $err = "❌ Token bo'sh bo'lishi mumkin emas!";
        }
    }

    if ($action === 'test_bot') {
        $chat_id = trim($_POST['test_chat_id'] ?? '');
        if ($chat_id) {
            $result = sendTelegram($chat_id, "✅ <b>ZiyoCRM</b> bot muvaffaqiyatli ulandi!\n\nSiz endi tizim xabarlarini qabul qila olasiz.");
            $msg = $result ? "✅ Test xabari yuborildi!" : "❌ Xabar yuborilmadi. Token yoki Chat ID ni tekshiring.";
        } else {
            $err = "❌ Chat ID kiriting!";
        }
    }

    if ($action === 'save_chat_id') {
        $user_id = (int)$_POST['user_id'];
        $chat_id = trim($_POST['chat_id'] ?? '');
        if ($user_id && $chat_id) {
            $db->prepare("UPDATE users SET telegram_chat_id=? WHERE id=?")->execute([$chat_id, $user_id]);
            $msg = "✅ Chat ID saqlandi!";
        }
    }
}

// Barcha foydalanuvchilar
$users = $db->query("SELECT id, full_name, role, username, telegram_chat_id FROM users WHERE is_active=1 ORDER BY role, full_name")->fetchAll(PDO::FETCH_ASSOC);
$role_labels = ['admin'=>'👑','teacher'=>'👨‍🏫','student'=>'🎓','parent'=>'👪'];
$linked_count   = count(array_filter($users, fn($u) => !empty($u['telegram_chat_id'])));
$unlinked_count = count($users) - $linked_count;

// So'nggi loglar (state larni chiqarmaslik)
$logs = $db->query("SELECT * FROM telegram_logs WHERE status != 'state' ORDER BY sent_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Telegram Sozlash — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">✈️ Telegram Bot Sozlash</div>
      <div class="topbar-right">
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

      <!-- Bot Status Cards -->
      <?php if ($is_configured && $me): ?>
      <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-bottom:20px">
        <div class="data-table-wrapper fade-in" style="padding:18px; text-align:center">
          <div style="font-size:28px; margin-bottom:6px">🤖</div>
          <div style="font-size:12px; color:var(--text-muted)">Bot nomi</div>
          <div style="font-weight:700; margin-top:4px"><?= htmlspecialchars($me['first_name'] ?? '—') ?></div>
          <div style="font-size:11px; color:#60a5fa; margin-top:2px">@<?= htmlspecialchars($me['username'] ?? $bot_username) ?></div>
        </div>
        <div class="data-table-wrapper fade-in" style="padding:18px; text-align:center">
          <div style="font-size:28px; margin-bottom:6px">✅</div>
          <div style="font-size:12px; color:var(--text-muted)">Bot holati</div>
          <div style="font-weight:700; color:#3fb950; margin-top:4px">Faol</div>
        </div>
        <div class="data-table-wrapper fade-in" style="padding:18px; text-align:center">
          <div style="font-size:28px; margin-bottom:6px">🔗</div>
          <div style="font-size:12px; color:var(--text-muted)">Ulangan</div>
          <div style="font-weight:700; color:#3fb950; margin-top:4px"><?= $linked_count ?> ta</div>
        </div>
        <div class="data-table-wrapper fade-in" style="padding:18px; text-align:center">
          <div style="font-size:28px; margin-bottom:6px">⏳</div>
          <div style="font-size:12px; color:var(--text-muted)">Kutilmoqda</div>
          <div style="font-weight:700; color:#f59e0b; margin-top:4px"><?= $unlinked_count ?> ta</div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Bot ulash yo'riqnomasi -->
      <div class="data-table-wrapper fade-in" style="padding:20px; margin-bottom:20px; background: linear-gradient(135deg, rgba(37,99,235,0.08), rgba(99,102,241,0.08)); border-color: rgba(99,102,241,0.2)">
        <h3 style="margin-bottom:12px">📱 Foydalanuvchilar botni qanday ulashi kerak?</h3>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; font-size:13px">
          <div>
            <p style="font-weight:600; margin-bottom:8px; color:#a5b4fc">1-usul: Bot orqali (tavsiya etiladi)</p>
            <ol style="padding-left:16px; line-height:2">
              <li>Telegramda <code>@<?= htmlspecialchars($bot_username) ?></code> ga o'ting</li>
              <li><strong>/start</strong> yuboring</li>
              <li><strong>🔗 Akkauntni ulash</strong> tugmasini bosing</li>
              <li>Tizim login va parolni kiriting</li>
              <li>Tayyor! ✅</li>
            </ol>
          </div>
          <div>
            <p style="font-weight:600; margin-bottom:8px; color:#a5b4fc">2-usul: ID orqali (qo'lda)</p>
            <ol style="padding-left:16px; line-height:2">
              <li><code>@userinfobot</code> yoki <code>/myid</code> orqali Chat ID oling</li>
              <li>Quyidagi jadvalda tegishli foydalanuvchiga ID kiriting</li>
              <li>✏️ Tahrirlash tugmasini bosib saqlang</li>
            </ol>
          </div>
        </div>
        <div style="margin-top:16px; padding:16px; background:rgba(255,255,255,0.03); border-radius:12px; border:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:20px; flex-wrap:wrap">
          <div style="background:#fff; padding:10px; border-radius:12px; display:inline-block">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://t.me/<?= htmlspecialchars($bot_username) ?>" alt="Telegram Bot QR" style="width:130px; height:130px; display:block">
          </div>
          <div style="flex:1; min-width:200px">
            <h4 style="color:#fff; margin-bottom:6px">📌 Telegram Bot QR-Kodi</h4>
            <p style="font-size:12px; color:#94a3b8; margin-bottom:12px; line-height:1.5">
              Ushbu QR kodni yuklab olib, e'lonlar taxtasiga osib qo'yishingiz yoki ota-onalar va o'quvchilarga tarqatishingiz mumkin. Skanerlashganda to'g'ri <code>@<?= htmlspecialchars($bot_username) ?></code> ga olib kiradi.
            </p>
            <div style="display:flex; gap:10px">
              <a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=https://t.me/<?= htmlspecialchars($bot_username) ?>" download="ZiyoCRM_BOT_QR.png" target="_blank" class="btn btn-primary btn-sm">
                📥 QR Kodni Yuklab Olish (HD)
              </a>
              <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Chop etish</button>
            </div>
          </div>
        </div>

        <div style="margin-top:12px; padding:10px 14px; background:rgba(99,102,241,0.08); border-radius:8px; font-size:12px">
          🔗 Bot havolasi: <a href="https://t.me/<?= htmlspecialchars($bot_username) ?>" target="_blank" style="color:#60a5fa">https://t.me/<?= htmlspecialchars($bot_username) ?></a>
        </div>
      </div>

      <!-- Bot holati --  Token + Test -->
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px">

        <!-- Token sozlash -->
        <div class="data-table-wrapper fade-in" style="padding:24px">
          <h3 style="margin-bottom:6px">🤖 Bot Token</h3>
          <p style="font-size:12px; color:var(--text-muted); margin-bottom:16px">
            @BotFather dan olingan tokenni kiriting
          </p>

          <?php if ($is_configured): ?>
          <div class="alert alert-success">✅ Bot token o'rnatilgan! (<code>@<?= htmlspecialchars($me['username'] ?? $bot_username) ?></code>)</div>
          <?php else: ?>
          <div class="alert alert-warning">⚠️ Bot token o'rnatilmagan!</div>
          <?php endif; ?>

          <form method="POST">
            <input type="hidden" name="action" value="save_token">
            <div class="form-group">
              <label>Bot Token</label>
              <input type="text" name="bot_token" 
                     placeholder="1234567890:ABCdef..."
                     value="<?= $is_configured ? htmlspecialchars($bot_token) : '' ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">💾 Saqlash</button>
          </form>
        </div>

        <!-- Test xabar -->
        <div class="data-table-wrapper fade-in" style="padding:24px">
          <h3 style="margin-bottom:6px">🔬 Test Xabar</h3>
          <p style="font-size:12px; color:var(--text-muted); margin-bottom:16px">
            Botni tekshirish uchun test xabar yuboring
          </p>

          <div style="background:rgba(37,99,235,0.1); border:1px solid rgba(37,99,235,0.2); border-radius:10px; padding:14px; margin-bottom:16px; font-size:12px;">
            <p style="font-weight:600; margin-bottom:6px">📌 Chat ID olish usuli:</p>
            <p>1. Botga <code>/myid</code> yuboring</p>
            <p>2. Yoki <code>@userinfobot</code> ga /start yuboring</p>
            <p>3. Shu raqamni kiriting va test qiling</p>
          </div>

          <form method="POST">
            <input type="hidden" name="action" value="test_bot">
            <div class="form-group">
              <label>Chat ID</label>
              <input type="text" name="test_chat_id" placeholder="123456789">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" <?= !$is_configured ? 'disabled' : '' ?>>
              📤 Test yuborish
            </button>
          </form>
        </div>
      </div>


      <!-- Foydalanuvchilar Chat ID -->
      <div class="data-table-wrapper fade-in" style="margin-bottom:20px">
        <div class="table-header">
          <h3>👥 Foydalanuvchilar Telegram Chat ID</h3>
        </div>
        <div style="padding:16px; font-size:12px; color:var(--text-muted); background:rgba(37,99,235,0.05); border-bottom:1px solid var(--border)">
          💡 Har bir foydalanuvchi <code>@userinfobot</code> orqali Chat ID ni topib, sizga yuborsin. Siz quyida saqlaysiz.
          Yoki foydalanuvchi <code>/start</code> yuborganida bot avtomatik saqlab qo'yadi.
        </div>
        <table>
          <thead>
            <tr>
              <th>Rol</th>
              <th>Ism</th>
              <th>Username</th>
              <th>Telegram Chat ID</th>
              <th>Holat</th>
              <th>O'zgartirish</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= $role_labels[$u['role']] ?? '' ?></td>
              <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
              <td><code style="color:#a78bfa"><?= htmlspecialchars($u['username']) ?></code></td>
              <td>
                <?php if ($u['telegram_chat_id']): ?>
                  <code style="color:#6ee7b7"><?= htmlspecialchars($u['telegram_chat_id']) ?></code>
                <?php else: ?>
                  <span style="color:var(--text-muted); font-size:12px">Ulanmagan</span>
                <?php endif; ?>
              </td>
              <td>
                <?= $u['telegram_chat_id']
                  ? '<span class="badge badge-success">✅ Ulangan</span>'
                  : '<span class="badge badge-warning">⏳ Kutilmoqda</span>'; ?>
              </td>
              <td>
                <button class="btn btn-outline btn-sm" onclick="editChatId(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>', '<?= htmlspecialchars($u['telegram_chat_id'] ?? '') ?>')">
                  ✏️ Tahrirlash
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Loglar -->
      <div class="data-table-wrapper fade-in">
        <div class="table-header">
          <h3>📋 So'nggi Telegram Loglari</h3>
        </div>
        <?php if (empty($logs)): ?>
        <div class="empty-state"><div class="empty-icon">📭</div><p>Hali log yo'q</p></div>
        <?php else: ?>
        <table>
          <thead><tr><th>Chat ID</th><th>Xabar</th><th>Holat</th><th>Vaqt</th></tr></thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
              <td><code><?= htmlspecialchars($log['chat_id']) ?></code></td>
              <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"><?= htmlspecialchars(strip_tags($log['message'])) ?></td>
              <td><?= $log['status']==='sent' ? '<span class="badge badge-success">✅ Yuborildi</span>' : '<span class="badge badge-danger">❌ Xato</span>' ?></td>
              <td><?= date('d.m H:i', strtotime($log['sent_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Chat ID tahrirlash modali -->
<div class="modal-overlay" id="editChatModal">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Chat ID O'zgartirish</h3>
      <button class="modal-close" onclick="closeModal('editChatModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_chat_id">
      <input type="hidden" name="user_id" id="editUserId">
      <div class="form-group">
        <label>Foydalanuvchi</label>
        <input type="text" id="editUserName" disabled style="opacity:0.6">
      </div>
      <div class="form-group">
        <label>Telegram Chat ID</label>
        <input type="text" name="chat_id" id="editChatIdInput" placeholder="123456789" required>
      </div>
      <button type="submit" class="btn btn-primary">💾 Saqlash</button>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('show'); });
});

function editChatId(userId, userName, chatId) {
  document.getElementById('editUserId').value = userId;
  document.getElementById('editUserName').value = userName;
  document.getElementById('editChatIdInput').value = chatId;
  openModal('editChatModal');
}
</script>
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
