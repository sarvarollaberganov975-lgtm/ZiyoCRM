<?php
require_once '../includes/config.php';
requireLogin('student');
$db = getDB();
$user = getCurrentUser();
$sid = $user['id'];

$group_ids = $db->prepare("SELECT group_id FROM student_groups WHERE student_id=?");
$group_ids->execute([$sid]);
$gids = array_column($group_ids->fetchAll(PDO::FETCH_ASSOC), 'group_id');

$homeworks = [];
if ($gids) {
    $in = implode(',', $gids);
    $homeworks = $db->query("
        SELECT h.*, g.name AS group_name, u.full_name AS teacher_name
        FROM homeworks h 
        JOIN groups g ON h.group_id=g.id
        JOIN users u ON h.teacher_id=u.id
        WHERE h.group_id IN ($in)
        ORDER BY h.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Uy Vazifalari — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📝 Uy Vazifalari</div>
      <div class="topbar-right">
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <?php if (empty($homeworks)): ?>
      <div class="empty-state"><div class="empty-icon">📝</div><p>Hozircha uy vazifasi yo'q</p></div>
      <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:12px">
        <?php foreach ($homeworks as $h): ?>
        <div class="data-table-wrapper fade-in" style="padding:20px">
          <div style="display:flex; align-items:flex-start; gap:16px">
            <div style="font-size:28px">📚</div>
            <div style="flex:1">
              <div style="display:flex; gap:8px; align-items:center; margin-bottom:6px">
                <span class="badge badge-primary"><?= htmlspecialchars($h['group_name']) ?></span>
                <?php if ($h['due_date']): ?>
                <span class="badge badge-warning">📅 <?= date('d.m.Y', strtotime($h['due_date'])) ?> gacha</span>
                <?php endif; ?>
              </div>
              <h3 style="font-size:15px; margin-bottom:6px"><?= htmlspecialchars($h['title']) ?></h3>
              <?php if ($h['description']): ?>
              <p style="font-size:13px; color:var(--text-muted); line-height:1.6"><?= nl2br(htmlspecialchars($h['description'])) ?></p>
              <?php endif; ?>
              <div style="font-size:11px; color:var(--text-muted); margin-top:8px">
                👨‍🏫 <?= htmlspecialchars($h['teacher_name']) ?> • <?= date('d.m.Y H:i', strtotime($h['created_at'])) ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
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
