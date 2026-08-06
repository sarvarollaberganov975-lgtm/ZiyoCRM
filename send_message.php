<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

$msg = $err = '';
$sent_messages = [];

// Xabar yuborish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_direct') {
        $chat_id    = trim($_POST['chat_id']    ?? '');
        $message    = trim($_POST['message']    ?? '');

        if (!$chat_id) { $err = "❌ Foydalanuvchi tanlanmagan!"; }
        elseif (!$message) { $err = "❌ Xabar yozing!"; }
        else {
            $full_msg = $message . "\n\n<i>— ZiyoCRM Admin</i>";
            $result = sendTelegram($chat_id, $full_msg);
            if ($result) {
                $msg = "✅ Xabar yuborildi!";
            } else {
                $err = "❌ Xabar yuborilmadi! Foydalanuvchi botni ishga tushirganmi?";
            }
        }
    }

    if ($action === 'send_selected') {
        $selected_ids = $_POST['student_ids'] ?? [];
        $message      = trim($_POST['message'] ?? '');
        $title        = trim($_POST['title']   ?? '');

        if (empty($selected_ids)) { $err = "❌ Kamida bitta o'quvchi tanlang!"; }
        elseif (!$message) { $err = "❌ Xabar matnini kiriting!"; }
        else {
            $full_msg = "📢 <b>" . htmlspecialchars($title ?: 'ZiyoCRM') . "</b>\n\n" . htmlspecialchars($message) . "\n\n<i>— ZiyoCRM Admin</i>";
            $sent = 0;
            $in_clause = implode(',', array_map('intval', $selected_ids));
            $rows = $db->query("SELECT telegram_chat_id FROM users WHERE id IN ($in_clause) AND telegram_chat_id IS NOT NULL AND is_active=1")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                sendTelegram($row['telegram_chat_id'], $full_msg);
                $sent++;
                usleep(50000);
            }
            $msg = "✅ Xabar tanlangan $sent ta o'quvchiga yuborildi!";
        }
    }

    if ($action === 'send_broadcast') {
        $target_type = $_POST['target_type'] ?? 'everyone';
        $message     = trim($_POST['message'] ?? '');
        $title       = trim($_POST['title']   ?? '');

        if (!$message) { $err = "❌ Xabar matnini kiriting!"; }
        else {
            $icon = '📢';
            $full_msg = $icon . " <b>" . htmlspecialchars($title ?: 'ZiyoCRM') . "</b>\n\n" . htmlspecialchars($message) . "\n\n<i>— ZiyoCRM Admin</i>";
            $sent = 0;

            $roleMap = [
                'everyone'     => "SELECT telegram_chat_id FROM users WHERE telegram_chat_id IS NOT NULL AND is_active=1",
                'all_students' => "SELECT telegram_chat_id FROM users WHERE role='student' AND telegram_chat_id IS NOT NULL AND is_active=1",
                'all_parents'  => "SELECT telegram_chat_id FROM users WHERE role='parent'  AND telegram_chat_id IS NOT NULL AND is_active=1",
                'all_teachers' => "SELECT telegram_chat_id FROM users WHERE role='teacher' AND telegram_chat_id IS NOT NULL AND is_active=1",
            ];

            $sql = $roleMap[$target_type] ?? $roleMap['everyone'];
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                sendTelegram($row['telegram_chat_id'], $full_msg);
                $sent++;
                usleep(50000); // 50ms oraliq - Telegram rate limit
            }
            $msg = "✅ Xabar $sent nafarga yuborildi!";
        }
    }
}

// Ulangan foydalanuvchilar
$users   = $db->query("SELECT id, full_name, role, telegram_chat_id FROM users WHERE is_active=1 AND telegram_chat_id IS NOT NULL ORDER BY role, full_name")->fetchAll(PDO::FETCH_ASSOC);
$total   = $db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$linked  = count($users);

$roleLabel = ['admin'=>'👑','teacher'=>'👨‍🏫','student'=>'🎓','parent'=>'👪'];
$roleName  = ['admin'=>'Admin','teacher'=>"O'qituvchi",'student'=>"O'quvchi",'parent'=>'Ota-ona'];
?><!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Xabar Yuborish — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<style>
/* ── Chat tarzidagi UI ── */
.msg-tabs { display:flex; gap:8px; margin-bottom:20px; }
.msg-tab {
  padding: 10px 20px; border-radius: 10px; cursor: pointer;
  font-size: 13px; font-weight: 600; border: 1.5px solid var(--border);
  background: transparent; color: var(--text-muted); transition: all .2s;
}
.msg-tab.active, .msg-tab:hover {
  background: rgba(99,102,241,.15); border-color: #6366f1; color: #a5b4fc;
}

.chat-layout { display: grid; grid-template-columns: 280px 1fr; gap: 16px; }

/* Foydalanuvchilar ro'yxati */
.user-list { display: flex; flex-direction: column; gap: 6px; max-height: 520px; overflow-y: auto; }
.user-list::-webkit-scrollbar { width: 4px; }
.user-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

.user-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px; cursor: pointer;
  border: 1.5px solid transparent; transition: all .2s;
}
.user-item:hover { background: rgba(255,255,255,.04); border-color: var(--border); }
.user-item.selected { background: rgba(99,102,241,.12); border-color: #6366f1; }

.user-avatar-sm {
  width: 38px; height: 38px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700; flex-shrink: 0;
}
.av-admin   { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
.av-teacher { background: linear-gradient(135deg, #1d4ed8, #1e40af); }
.av-student { background: linear-gradient(135deg, #047857, #065f46); }
.av-parent  { background: linear-gradient(135deg, #b45309, #92400e); }

.user-item-info { flex: 1; min-width: 0; }
.user-item-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-item-role { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

/* Xabar yozish maydoni */
.chat-box {
  display: flex; flex-direction: column; height: 520px;
  background: rgba(255,255,255,.02); border: 1.5px solid var(--border);
  border-radius: 14px; overflow: hidden;
}
.chat-header {
  padding: 14px 18px; border-bottom: 1px solid var(--border);
  background: rgba(255,255,255,.03);
  display: flex; align-items: center; gap: 10px;
}
.chat-header-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; background: linear-gradient(135deg, #7c3aed, #4f46e5);
}
.chat-header-name { font-size: 14px; font-weight: 700; }
.chat-header-sub  { font-size: 11px; color: var(--text-muted); }

.chat-body { flex: 1; padding: 16px; overflow-y: auto; display: flex; flex-direction: column; justify-content: flex-end; }
.chat-placeholder {
  text-align: center; color: var(--text-muted); font-size: 13px;
  display: flex; flex-direction: column; align-items: center; gap: 10px;
}

.chat-footer {
  padding: 12px 14px; border-top: 1px solid var(--border);
  background: rgba(255,255,255,.02);
  display: flex; gap: 10px; align-items: flex-end;
}
.chat-input {
  flex: 1; background: rgba(255,255,255,.05); border: 1.5px solid var(--border);
  border-radius: 12px; padding: 10px 14px; color: var(--text);
  font-family: inherit; font-size: 14px; resize: none;
  max-height: 120px; outline: none; transition: border-color .2s;
  line-height: 1.5;
}
.chat-input:focus { border-color: #6366f1; }
.chat-input::placeholder { color: var(--text-muted); }

.chat-send-btn {
  width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer;
  background: linear-gradient(135deg, #7c3aed, #6d28d9);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; transition: all .2s; flex-shrink: 0;
}
.chat-send-btn:hover { transform: scale(1.08); box-shadow: 0 4px 16px rgba(109,40,217,.5); }
.chat-send-btn:disabled { opacity: .4; cursor: not-allowed; transform: none; }

/* Broadcast */
.broadcast-box { padding: 24px; }
.broadcast-targets { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
.target-card {
  border: 1.5px solid var(--border); border-radius: 12px; padding: 14px;
  cursor: pointer; transition: all .2s; text-align: center; background: transparent;
}
.target-card:hover, .target-card.active {
  border-color: #6366f1; background: rgba(99,102,241,.1);
}
.target-card.active { border-color: #6366f1; }
.target-icon { font-size: 24px; margin-bottom: 6px; }
.target-label { font-size: 12px; font-weight: 600; }

.stats-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
  background: rgba(99,102,241,.15); color: #a5b4fc;
}
</style>
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">💬 Telegram Xabar Yuborish</div>
      <div class="topbar-right">
        <span class="stats-pill">🔗 <?= $linked ?>/<?= $total ?> ulangan</span>
        <a href="../logout.php" class="btn btn-outline btn-sm" style="margin-left:10px">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">

      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

      <!-- Tablar -->
      <div class="msg-tabs">
        <button class="msg-tab active" id="tab-direct"    onclick="switchTab('direct')">💬 Bitta odamga</button>
        <button class="msg-tab"        id="tab-selected"  onclick="switchTab('selected')">☑️ Tanlanganlarga</button>
        <button class="msg-tab"        id="tab-broadcast" onclick="switchTab('broadcast')">📢 Hammaga yuborish</button>
      </div>

      <!-- ═══ SELECTED STUDENTS TAB ═══ -->
      <div id="panel-selected" class="data-table-wrapper fade-in" style="display:none; padding:24px">
        <h3 style="margin-bottom:16px">☑️ Tanlangan o'quvchi/foydalanuvchilarga xabar yuborish</h3>
        
        <form method="POST">
          <input type="hidden" name="action" value="send_selected">

          <div style="display:flex; justify-size:space-between; align-items:center; margin-bottom:12px; gap:10px; flex-wrap:wrap">
            <input type="text" id="studentSearch" placeholder="🔍 O'quvchi qidirish..." style="max-width:250px; padding:8px 12px; border-radius:8px; border:1px solid var(--border); background:rgba(255,255,255,0.05); color:#fff;" onkeyup="filterStudents()">
            <div>
              <button type="button" class="btn btn-outline btn-sm" onclick="toggleSelectAll(true)">✓ Barchasini belgilash</button>
              <button type="button" class="btn btn-outline btn-sm" onclick="toggleSelectAll(false)">✕ Belgilashni bekor qilish</button>
            </div>
          </div>

          <div style="max-height:220px; overflow-y:auto; border:1px solid var(--border); border-radius:10px; padding:12px; margin-bottom:20px; background:rgba(0,0,0,0.2); display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:10px;" id="studentGrid">
            <?php foreach ($users as $u): ?>
            <label class="student-select-card" style="display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.03); cursor:pointer; border:1px solid rgba(255,255,255,0.05);">
              <input type="checkbox" name="student_ids[]" value="<?= $u['id'] ?>" class="st-check" style="width:16px; height:16px;">
              <span style="font-size:13px; font-weight:600; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;"><?= htmlspecialchars($u['full_name']) ?></span>
              <small style="margin-left:auto; font-size:10px; opacity:0.6;"><?= $roleName[$u['role']] ?></small>
            </label>
            <?php endforeach; ?>
          </div>

          <div class="form-group">
            <label>📋 Sarlavha (ixtiyoriy)</label>
            <input type="text" name="title" placeholder="Masalan: Muhim ogohlantirish...">
          </div>

          <div class="form-group">
            <label>✍️ Xabar matni *</label>
            <textarea name="message" rows="4" placeholder="Tanlangan o'quvchilarga boradigan xabar matni..." required style="resize:vertical"></textarea>
          </div>

          <button type="submit" class="btn btn-primary">📤 Tanlanganlarga Yuborish</button>
        </form>
      </div>

      <!-- ═══ DIRECT TAB ═══ -->
      <div id="panel-direct" class="data-table-wrapper fade-in" style="padding:20px">
        <div class="chat-layout">

          <!-- Chap: foydalanuvchilar -->
          <div>
            <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:10px">
              🔗 Ulangan foydalanuvchilar (<?= $linked ?>)
            </div>
            <?php if (empty($users)): ?>
            <div style="text-align:center; padding:30px; color:var(--text-muted); font-size:13px">
              😔 Hali hech kim ulangmagan<br>
              <small>Foydalanuvchilar @ziyo_crm_bot ga /start yuborganida bu yerda ko'rinadi</small>
            </div>
            <?php else: ?>
            <div class="user-list">
              <?php foreach ($users as $u): ?>
              <div class="user-item" id="user-<?= $u['id'] ?>" onclick="selectUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>', '<?= $u['role'] ?>', '<?= $u['telegram_chat_id'] ?>')">
                <div class="user-avatar-sm av-<?= $u['role'] ?>">
                  <?= strtoupper(mb_substr($u['full_name'], 0, 1)) ?>
                </div>
                <div class="user-item-info">
                  <div class="user-item-name"><?= htmlspecialchars($u['full_name']) ?></div>
                  <div class="user-item-role"><?= $roleLabel[$u['role']] ?> <?= $roleName[$u['role']] ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- O'ng: chat oynasi -->
          <div class="chat-box">
            <!-- Header -->
            <div class="chat-header" id="chatHeader">
              <div class="chat-header-avatar">💬</div>
              <div>
                <div class="chat-header-name" id="chatName">Foydalanuvchi tanlang</div>
                <div class="chat-header-sub"  id="chatRole">Chap tarafdan odamni bosing</div>
              </div>
            </div>

            <!-- Body -->
            <div class="chat-body">
              <div class="chat-placeholder" id="chatPlaceholder">
                <div style="font-size:48px">💬</div>
                <div>Foydalanuvchini tanlang va xabar yozing</div>
                <div style="font-size:11px">Xabar to'g'ridan-to'g'ri Telegram ga boradi</div>
              </div>
            </div>

            <!-- Footer -->
            <form method="POST" id="directForm">
              <input type="hidden" name="action"  value="send_direct">
              <input type="hidden" name="chat_id" id="selectedChatId">
              <div class="chat-footer">
                <textarea class="chat-input" name="message" id="chatTextarea"
                  placeholder="Xabar yozing... (Enter — yuborish, Shift+Enter — yangi qator)"
                  rows="1" disabled></textarea>
                <button type="submit" class="chat-send-btn" id="sendBtn" disabled title="Yuborish">
                  ➤
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>

      <!-- ═══ BROADCAST TAB ═══ -->
      <div id="panel-broadcast" class="data-table-wrapper fade-in" style="display:none">
        <div class="broadcast-box">
          <h3 style="margin-bottom:20px">📢 Guruhga xabar yuborish</h3>

          <form method="POST">
            <input type="hidden" name="action" value="send_broadcast">

            <!-- Target cards -->
            <div style="margin-bottom:16px; font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px">Kimga?</div>
            <div class="broadcast-targets">
              <?php
              $targets = [
                'everyone'     => ['🌍', 'Hammaga',                 $linked],
                'all_students' => ['🎓', "Barcha o'quvchilar",       count(array_filter($users, fn($u)=>$u['role']==='student'))],
                'all_parents'  => ['👪', 'Barcha ota-onalar',         count(array_filter($users, fn($u)=>$u['role']==='parent'))],
                'all_teachers' => ['👨‍🏫', "Barcha o'qituvchilar",   count(array_filter($users, fn($u)=>$u['role']==='teacher'))],
              ];
              foreach ($targets as $val => [$icon, $label, $count]):
              ?>
              <label class="target-card <?= $val==='everyone'?'active':'' ?>" id="tc-<?= $val ?>">
                <input type="radio" name="target_type" value="<?= $val ?>" <?= $val==='everyone'?'checked':'' ?> style="display:none" onchange="highlightTarget('<?= $val ?>')">
                <div class="target-icon"><?= $icon ?></div>
                <div class="target-label"><?= $label ?></div>
                <div style="font-size:11px; color:var(--text-muted); margin-top:4px"><?= $count ?> kishi ulangan</div>
              </label>
              <?php endforeach; ?>
            </div>

            <div class="form-group">
              <label>📋 Sarlavha (ixtiyoriy)</label>
              <input type="text" name="title" placeholder="Masalan: Muhim e'lon, Dars jadvali...">
            </div>

            <div class="form-group">
              <label>✍️ Xabar matni *</label>
              <textarea name="message" rows="5" placeholder="Bu yerga xabar matnini yozing..." required
                style="resize:vertical"></textarea>
            </div>

            <div style="display:flex; gap:12px; align-items:center">
              <button type="submit" class="btn btn-primary">📤 Yuborish</button>
              <span style="font-size:12px; color:var(--text-muted)">
                ⚠️ Xabar barcha tanlangan foydalanuvchilarga Telegram orqali boradi
              </span>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
// ── Tab switching ──
function switchTab(tab) {
  document.getElementById('panel-direct').style.display    = tab==='direct'    ? 'block' : 'none';
  document.getElementById('panel-selected').style.display  = tab==='selected'  ? 'block' : 'none';
  document.getElementById('panel-broadcast').style.display = tab==='broadcast' ? 'block' : 'none';

  document.getElementById('tab-direct').classList.toggle('active',    tab==='direct');
  document.getElementById('tab-selected').classList.toggle('active',  tab==='selected');
  document.getElementById('tab-broadcast').classList.toggle('active', tab==='broadcast');
}

function filterStudents() {
  const query = document.getElementById('studentSearch').value.toLowerCase();
  document.querySelectorAll('#studentGrid .student-select-card').forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? 'flex' : 'none';
  });
}

function toggleSelectAll(select) {
  document.querySelectorAll('#studentGrid .st-check').forEach(chk => {
    if (chk.offsetParent !== null) { // Faqat ko'rinib turgan filtrlanuvchilarni
      chk.checked = select;
    }
  });
}

// ── User select ──
const roleColors = { admin:'#7c3aed', teacher:'#1d4ed8', student:'#047857', parent:'#b45309' };
const roleNames  = { admin:'Admin 👑', teacher:"O'qituvchi 👨‍🏫", student:"O'quvchi 🎓", parent:'Ota-ona 👪' };

function selectUser(id, name, role, chatId) {
  // Highlight
  document.querySelectorAll('.user-item').forEach(el => el.classList.remove('selected'));
  document.getElementById('user-' + id).classList.add('selected');

  // Update chat header
  document.getElementById('chatName').textContent = name;
  document.getElementById('chatRole').textContent = roleNames[role] || role;
  const av = document.querySelector('#chatHeader .chat-header-avatar');
  av.style.background = `linear-gradient(135deg, ${roleColors[role]||'#6366f1'}, #4f46e5)`;
  av.textContent = name.charAt(0).toUpperCase();

  // Set chat_id
  document.getElementById('selectedChatId').value = chatId;

  // Enable input
  const ta  = document.getElementById('chatTextarea');
  const btn = document.getElementById('sendBtn');
  ta.disabled  = false;
  btn.disabled = false;
  ta.placeholder = name + ' ga xabar yozing...';
  ta.focus();

  // Hide placeholder
  document.getElementById('chatPlaceholder').style.display = 'none';
}

// ── Auto-resize textarea ──
document.getElementById('chatTextarea').addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// ── Enter = send, Shift+Enter = newline ──
document.getElementById('chatTextarea').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    if (!this.disabled && this.value.trim()) {
      document.getElementById('directForm').submit();
    }
  }
});

// ── Broadcast target highlight ──
function highlightTarget(val) {
  document.querySelectorAll('.target-card').forEach(el => el.classList.remove('active'));
  document.getElementById('tc-' + val).classList.add('active');
}
document.querySelectorAll('.target-card').forEach(el => {
  el.addEventListener('click', () => {
    const r = el.querySelector('input[type=radio]');
    if (r) { r.checked = true; highlightTarget(r.value); }
  });
});

<?php if ($msg && strpos($msg, '✅') !== false): ?>
// Muvaffaqiyatli yuborilgandan keyin textarea ni tozalash
document.getElementById('chatTextarea') && (document.getElementById('chatTextarea').value = '');
<?php endif; ?>
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
