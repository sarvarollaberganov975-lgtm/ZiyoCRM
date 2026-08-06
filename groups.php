<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_group') {
        $name       = trim($_POST['name'] ?? '');
        $subject    = trim($_POST['subject'] ?? '');
        $teacher_id = (int)$_POST['teacher_id'];
        if ($name) {
            $db->prepare("INSERT INTO groups (name, subject, teacher_id) VALUES (?,?,?)")
               ->execute([$name, $subject, $teacher_id ?: null]);
            $msg = "✅ Guruh qo'shildi!";
        } else $err = "❌ Guruh nomini kiriting!";
    }

    if ($action === 'add_student_group') {
        $sid = (int)$_POST['student_id'];
        $gid = (int)$_POST['group_id'];
        $exists = $db->prepare("SELECT id FROM student_groups WHERE student_id=? AND group_id=?");
        $exists->execute([$sid, $gid]);
        if (!$exists->fetch()) {
            $db->prepare("INSERT INTO student_groups (student_id, group_id) VALUES (?,?)")->execute([$sid, $gid]);
            $msg = "✅ O'quvchi guruhga qo'shildi!";
        } else $err = "⚠️ Bu o'quvchi allaqachon guruhda!";
    }

    if ($action === 'delete_group') {
        $id = (int)$_POST['group_id'];
        $db->prepare("DELETE FROM groups WHERE id=?")->execute([$id]);
        $db->prepare("DELETE FROM student_groups WHERE group_id=?")->execute([$id]);
        $msg = "✅ Guruh o'chirildi!";
    }
}

$groups   = $db->query("
    SELECT g.*, u.full_name as teacher_name, COUNT(sg.student_id) as student_count
    FROM groups g 
    LEFT JOIN users u ON g.teacher_id = u.id
    LEFT JOIN student_groups sg ON g.id = sg.group_id
    GROUP BY g.id ORDER BY g.name
")->fetchAll(PDO::FETCH_ASSOC);

$teachers = $db->query("SELECT id, full_name FROM users WHERE role='teacher' AND is_active=1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$students = $db->query("SELECT id, full_name FROM users WHERE role='student' AND is_active=1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Guruhlar — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📚 Guruhlar</div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('addGroupModal')">➕ Guruh qo'shish</button>
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

      <!-- O'quvchi qo'shish -->
      <div class="data-table-wrapper fade-in" style="padding:20px; margin-bottom:20px">
        <h3 style="margin-bottom:14px; font-size:14px">👤 O'quvchini guruhga qo'shish</h3>
        <form method="POST" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end">
          <input type="hidden" name="action" value="add_student_group">
          <div class="form-group" style="margin:0; flex:1; min-width:180px">
            <label>O'quvchi</label>
            <select name="student_id" required>
              <option value="">— Tanlang —</option>
              <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0; flex:1; min-width:180px">
            <label>Guruh</label>
            <select name="group_id" required>
              <option value="">— Tanlang —</option>
              <?php foreach ($groups as $g): ?>
              <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-success btn-sm" style="height:42px">➕ Qo'shish</button>
        </form>
      </div>

      <!-- Guruhlar -->
      <div class="data-table-wrapper fade-in">
        <div class="table-header"><h3>📚 Barcha Guruhlar</h3></div>
        <?php if (empty($groups)): ?>
        <div class="empty-state"><div class="empty-icon">📚</div><p>Hali guruh yo'q</p></div>
        <?php else: ?>
        <table>
          <thead><tr><th>#</th><th>Guruh nomi</th><th>Fan</th><th>O'qituvchi</th><th>O'quvchilar</th><th>Qo'shildi</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($groups as $i => $g): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
              <td><?= htmlspecialchars($g['subject'] ?: '—') ?></td>
              <td><?= htmlspecialchars($g['teacher_name'] ?: '—') ?></td>
              <td><span class="badge badge-primary"><?= $g['student_count'] ?> ta</span></td>
              <td><?= date('d.m.Y', strtotime($g['created_at'])) ?></td>
              <td>
                <form method="POST" style="display:inline" onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
                  <input type="hidden" name="action" value="delete_group">
                  <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="addGroupModal">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Yangi Guruh</h3>
      <button class="modal-close" onclick="closeModal('addGroupModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_group">
      <div class="form-group">
        <label>Guruh nomi *</label>
        <input type="text" name="name" placeholder="Kimyo-101" required>
      </div>
      <div class="form-group">
        <label>Fan</label>
        <input type="text" name="subject" placeholder="Matematika, Fizika...">
      </div>
      <div class="form-group">
        <label>O'qituvchi</label>
        <select name="teacher_id">
          <option value="">— Tanlang —</option>
          <?php foreach ($teachers as $t): ?>
          <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">✅ Saqlash</button>
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
