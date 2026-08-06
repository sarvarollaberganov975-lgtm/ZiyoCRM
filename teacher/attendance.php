<?php
require_once '../includes/config.php';
requireLogin('teacher');
$db = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $date       = $_POST['date'] ?? date('Y-m-d');
        $group_id   = (int)$_POST['group_id'];
        $students   = $_POST['students'] ?? [];
        $statuses   = $_POST['statuses'] ?? [];
        $notes      = $_POST['notes'] ?? [];

        foreach ($students as $sid) {
            $status = $statuses[$sid] ?? 'present';
            $note   = $notes[$sid] ?? '';

            // Mavjudligini tekshir
            $exists = $db->prepare("SELECT id FROM attendance WHERE student_id=? AND group_id=? AND date=?");
            $exists->execute([$sid, $group_id, $date]);
            if ($exists->fetch()) {
                $db->prepare("UPDATE attendance SET status=?, note=? WHERE student_id=? AND group_id=? AND date=?")
                   ->execute([$status, $note, $sid, $group_id, $date]);
            } else {
                $db->prepare("INSERT INTO attendance (student_id, group_id, date, status, note) VALUES (?,?,?,?,?)")
                   ->execute([$sid, $group_id, $date, $status, $note]);
            }

            // Kech qolishda xabar yuborish
            if ($status === 'absent' || $status === 'late') {
                $student = $db->query("SELECT * FROM users WHERE id=$sid")->fetch(PDO::FETCH_ASSOC);
                $status_uz = $status === 'absent' ? "❌ Kelmadi" : "⏰ Kech qoldi";
                $att_msg = "📅 <b>DAVOMAT XABARI</b>\n\n"
                    . "Hurmatli <b>{$student['full_name']}</b>,\n\n"
                    . "Bugun ($date) sizning davomatingiz:\n"
                    . "<b>$status_uz</b>\n\n— ZiyoCRM";

                sendTelegram($student['telegram_chat_id'], $att_msg);

                $parents = $db->prepare("SELECT u.telegram_chat_id FROM parent_student ps JOIN users u ON ps.parent_id=u.id WHERE ps.student_id=?");
                $parents->execute([$sid]);
                foreach ($parents->fetchAll(PDO::FETCH_ASSOC) as $p) {
                    sendTelegram($p['telegram_chat_id'], $att_msg);
                }
            }
        }
        $msg = "✅ Davomat saqlandi!";
    }
}

$groups   = $db->query("SELECT g.*, u.full_name AS teacher_name FROM groups g LEFT JOIN users u ON g.teacher_id=u.id ORDER BY g.name")->fetchAll(PDO::FETCH_ASSOC);
$sel_group = (int)($_GET['group_id'] ?? 0);
$sel_date  = $_GET['date'] ?? date('Y-m-d');

$group_students = [];
if ($sel_group) {
    $gs = $db->prepare("
        SELECT u.id, u.full_name,
               a.status, a.note
        FROM student_groups sg
        JOIN users u ON sg.student_id=u.id
        LEFT JOIN attendance a ON a.student_id=u.id AND a.group_id=? AND a.date=?
        WHERE sg.group_id=?
        ORDER BY u.full_name
    ");
    $gs->execute([$sel_group, $sel_date, $sel_group]);
    $group_students = $gs->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Davomat — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📅 Davomat</div>
      <div class="topbar-right">
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

      <!-- Filtr -->
      <div class="data-table-wrapper fade-in" style="padding:20px; margin-bottom:20px">
        <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">
          <div class="form-group" style="margin:0; flex:1; min-width:180px">
            <label>Guruh</label>
            <select name="group_id" required>
              <option value="">— Guruhni tanlang —</option>
              <?php foreach ($groups as $g): ?>
              <option value="<?= $g['id'] ?>" <?= $sel_group===$g['id']?'selected':'' ?>><?= htmlspecialchars($g['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0; min-width:160px">
            <label>Sana</label>
            <input type="date" name="date" value="<?= htmlspecialchars($sel_date) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="height:42px">🔍 Ochish</button>
        </form>
      </div>

      <!-- Davomat jadvali -->
      <?php if ($sel_group && !empty($group_students)): ?>
      <div class="data-table-wrapper fade-in">
        <div class="table-header">
          <h3>📋 Davomat — <?= date('d.m.Y', strtotime($sel_date)) ?></h3>
          <span style="font-size:12px; color:var(--text-muted)"><?= count($group_students) ?> nafar o'quvchi</span>
        </div>
        <form method="POST">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="group_id" value="<?= $sel_group ?>">
          <input type="hidden" name="date" value="<?= htmlspecialchars($sel_date) ?>">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>O'quvchi</th>
                <th>Keldi</th>
                <th>Kelmadi</th>
                <th>Kech qoldi</th>
                <th>Izoh</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($group_students as $i => $s): ?>
              <?php $cur_status = $s['status'] ?? 'present'; ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td>
                  <input type="hidden" name="students[]" value="<?= $s['id'] ?>">
                  <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                </td>
                <td>
                  <label style="cursor:pointer; display:flex; align-items:center; gap:6px">
                    <input type="radio" name="statuses[<?= $s['id'] ?>]" value="present" <?= $cur_status==='present'?'checked':'' ?>>
                    <span style="color:#6ee7b7">✅</span>
                  </label>
                </td>
                <td>
                  <label style="cursor:pointer; display:flex; align-items:center; gap:6px">
                    <input type="radio" name="statuses[<?= $s['id'] ?>]" value="absent" <?= $cur_status==='absent'?'checked':'' ?>>
                    <span style="color:#fca5a5">❌</span>
                  </label>
                </td>
                <td>
                  <label style="cursor:pointer; display:flex; align-items:center; gap:6px">
                    <input type="radio" name="statuses[<?= $s['id'] ?>]" value="late" <?= $cur_status==='late'?'checked':'' ?>>
                    <span style="color:#fcd34d">⏰</span>
                  </label>
                </td>
                <td>
                  <input type="text" name="notes[<?= $s['id'] ?>]" 
                         value="<?= htmlspecialchars($s['note'] ?? '') ?>"
                         placeholder="Izoh..."
                         style="background:rgba(255,255,255,0.06); border:1px solid var(--border); border-radius:6px; padding:6px 10px; color:var(--text); font-size:12px; width:140px; outline:none">
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div style="padding:16px">
            <button type="submit" class="btn btn-primary">💾 Davomatni saqlash</button>
            <span style="font-size:12px; color:var(--text-muted); margin-left:12px">
              ⚠️ Kelmagan/kech qolgan o'quvchilarga Telegram orqali xabar yuboriladi
            </span>
          </div>
        </form>
      </div>
      <?php elseif ($sel_group): ?>
      <div class="empty-state"><div class="empty-icon">👤</div><p>Bu guruhda o'quvchilar yo'q</p></div>
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
