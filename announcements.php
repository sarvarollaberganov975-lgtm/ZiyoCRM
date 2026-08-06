<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $target  = $_POST['target'] ?? 'all';
        if ($title && $content) {
            $db->prepare("INSERT INTO announcements (author_id, title, content, target) VALUES (?,?,?,?)")
               ->execute([$_SESSION['user_id'], $title, $content, $target]);

            // Telegram yuborish
            $tg_msg = "📢 <b>" . htmlspecialchars($title) . "</b>\n\n" . htmlspecialchars($content) . "\n\n— ZiyoCRM";

            $roles = ['all'=>['student','teacher','parent'],'students'=>['student'],'teachers'=>['teacher'],'parents'=>['parent']];
            $target_roles = $roles[$target] ?? ['student','teacher','parent'];

            $placeholders = implode(',', array_fill(0, count($target_roles), '?'));
            $users = $db->prepare("SELECT telegram_chat_id FROM users WHERE role IN ($placeholders) AND telegram_chat_id IS NOT NULL AND is_active=1");
            $users->execute($target_roles);
            foreach ($users->fetchAll(PDO::FETCH_ASSOC) as $u) {
                sendTelegram($u['telegram_chat_id'], $tg_msg);
            }

            $msg = "✅ E'lon yuborildi va Telegram orqali xabar yuborildi!";
        } else $err = "❌ Barcha maydonlarni to'ldiring!";
    }

    if ($action === 'delete') {
        $db->prepare("DELETE FROM announcements WHERE id=?")->execute([(int)$_POST['ann_id']]);
        $msg = "✅ E'lon o'chirildi!";
    }
}

$announcements = $db->query("
    SELECT a.*, u.full_name AS author_name
    FROM announcements a JOIN users u ON a.author_id=u.id
    ORDER BY a.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$target_labels = ['all'=>'🌍 Hammaga','students'=>"🎓 O'quvchilarga",'teachers'=>"👨‍🏫 O'qituvchilarga",'parents'=>'👪 Ota-onalarga'];
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>E'lonlar — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📢 E'lonlar</div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('addAnnModal')">➕ E'lon qo'shish</button>
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

      <?php if (empty($announcements)): ?>
      <div class="empty-state"><div class="empty-icon">📢</div><p>E'lonlar yo'q</p></div>
      <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:12px">
        <?php foreach ($announcements as $a): ?>
        <div class="data-table-wrapper fade-in" style="padding:20px">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px">
            <div style="flex:1">
              <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px">
                <span class="badge badge-primary"><?= $target_labels[$a['target']] ?? $a['target'] ?></span>
                <span style="font-size:11px; color:var(--text-muted)"><?= date('d.m.Y H:i', strtotime($a['created_at'])) ?></span>
              </div>
              <h3 style="font-size:15px; margin-bottom:8px"><?= htmlspecialchars($a['title']) ?></h3>
              <p style="font-size:13px; color:var(--text-muted); line-height:1.6"><?= nl2br(htmlspecialchars($a['content'])) ?></p>
            </div>
            <form method="POST" onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="modal-overlay" id="addAnnModal">
  <div class="modal">
    <div class="modal-header">
      <h3>📢 Yangi E'lon</h3>
      <button class="modal-close" onclick="closeModal('addAnnModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Kimga yuborish *</label>
        <select name="target">
          <option value="all">🌍 Hammaga</option>
          <option value="students">🎓 O'quvchilarga</option>
          <option value="teachers">👨‍🏫 O'qituvchilarga</option>
          <option value="parents">👪 Ota-onalarga</option>
        </select>
      </div>
      <div class="form-group">
        <label>Sarlavha *</label>
        <input type="text" name="title" placeholder="E'lon sarlavhasi" required>
      </div>
      <div class="form-group">
        <label>Matn *</label>
        <textarea name="content" rows="5" placeholder="E'lon matni..." required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">📢 Yuborish</button>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('show'); });
});
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
