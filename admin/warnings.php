<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

$msg = $err = '';

// TANBEH BERISH
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_warning') {
        $student_id  = (int)$_POST['student_id'];
        $teacher_id  = (int)$_POST['teacher_id'];
        $type        = trim($_POST['type'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($student_id && $teacher_id && $type && $description) {
            $db->prepare("INSERT INTO warnings (student_id, teacher_id, type, description) VALUES (?,?,?,?)")
               ->execute([$student_id, $teacher_id, $type, $description]);
            $warning_id = $db->lastInsertId();

            // O'quvchi ma'lumotlari
            $student = $db->query("SELECT * FROM users WHERE id=$student_id")->fetch(PDO::FETCH_ASSOC);
            $teacher = $db->query("SELECT full_name FROM users WHERE id=$teacher_id")->fetch(PDO::FETCH_ASSOC);
            $date = date('d.m.Y');

            // O'quvchiga Telegram xabar
            $student_msg = "⚠️ <b>TANBEH</b>\n\n"
                . "Hurmatli <b>{$student['full_name']}</b>,\n\n"
                . "Sizga quyidagi sababga ko'ra tanbeh berildi:\n\n"
                . "📌 <b>Sabab:</b> $type\n"
                . "📝 <b>Izoh:</b> $description\n"
                . "👨‍🏫 <b>O'qituvchi:</b> {$teacher['full_name']}\n"
                . "📅 <b>Sana:</b> $date\n\n"
                . "Iltimos, bundan keyin ehtiyot bo'ling.\n\n"
                . "— ZiyoCRM";

            $notified_student = sendTelegram($student['telegram_chat_id'], $student_msg) ? 1 : 0;

            // Ota-onaga Telegram xabar
            $parents = $db->prepare("
                SELECT u.telegram_chat_id, u.full_name 
                FROM parent_student ps 
                JOIN users u ON ps.parent_id = u.id 
                WHERE ps.student_id=?
            ");
            $parents->execute([$student_id]);
            $parent_list = $parents->fetchAll(PDO::FETCH_ASSOC);

            $notified_parent = 0;
            foreach ($parent_list as $parent) {
                $parent_msg = "⚠️ <b>TANBEH XABARI</b>\n\n"
                    . "Hurmatli <b>{$parent['full_name']}</b>,\n\n"
                    . "Farzandingiz <b>{$student['full_name']}</b> bugun tanbeh oldi.\n\n"
                    . "📌 <b>Sabab:</b> $type\n"
                    . "📝 <b>Izoh:</b> $description\n"
                    . "👨‍🏫 <b>O'qituvchi:</b> {$teacher['full_name']}\n"
                    . "📅 <b>Sana:</b> $date\n\n"
                    . "Farzandingiz bilan gaplashishni tavsiya etamiz.\n\n"
                    . "— ZiyoCRM";

                if (sendTelegram($parent['telegram_chat_id'], $parent_msg)) {
                    $notified_parent = 1;
                }
            }

            // Natijani yangilash
            $db->prepare("UPDATE warnings SET notified_student=?, notified_parent=? WHERE id=?")
               ->execute([$notified_student, $notified_parent, $warning_id]);

            // 3 tanbeh tekshirish
            $warn_count = $db->prepare("SELECT COUNT(*) FROM warnings WHERE student_id=?");
            $warn_count->execute([$student_id]);
            $count = $warn_count->fetchColumn();

            if ($count >= 3 && !empty($parent_list)) {
                $alert_msg = "🚨 <b>MUHIM OGOHLANTIRISH</b>\n\n"
                    . "Hurmatli ota-ona,\n\n"
                    . "Farzandingiz <b>{$student['full_name']}</b> jami <b>$count ta</b> tanbeh oldi!\n\n"
                    . "Maktab ma'muriyati bilan bog'lanishingizni so'raymiz.\n\n"
                    . "📞 ZiyoCRM administratsiyasi";

                foreach ($parent_list as $parent) {
                    sendTelegram($parent['telegram_chat_id'], $alert_msg);
                }
            }

            $msg = "✅ Tanbeh berildi va Telegram orqali xabar yuborildi!";
        } else {
            $err = "❌ Barcha maydonlarni to'ldiring!";
        }
    }

    if ($action === 'delete_warning') {
        $id = (int)$_POST['warning_id'];
        $db->prepare("DELETE FROM warnings WHERE id=?")->execute([$id]);
        $msg = "✅ Tanbeh o'chirildi!";
    }
}

// Ma'lumotlar
$warnings = $db->query("
    SELECT w.*, 
           s.full_name AS student_name, s.telegram_chat_id AS student_tg,
           t.full_name AS teacher_name
    FROM warnings w
    JOIN users s ON w.student_id = s.id
    JOIN users t ON w.teacher_id = t.id
    ORDER BY w.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$students = $db->query("SELECT id, full_name FROM users WHERE role='student' AND is_active=1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $db->query("SELECT id, full_name FROM users WHERE role='teacher' AND is_active=1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

$warning_types = [
    "Darsga kechikish",
    "Uy vazifasi bajarilmagan",
    "Darsni buzgan",
    "Mobil telefon ishlatgan",
    "Hurmatsizlik",
    "Sababsiz darsga kelmagan",
    "Kiyim qoidasini buzmagan",
    "Boshqa sabab"
];

// O'quvchi statistikasi
$student_stats = $db->query("
    SELECT student_id, COUNT(*) as cnt, u.full_name
    FROM warnings w JOIN users u ON w.student_id=u.id
    GROUP BY student_id ORDER BY cnt DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tanbehllar — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">⚠️ Tanbeh Tizimi</div>
      <div class="topbar-right">
        <button class="btn btn-danger btn-sm" onclick="openModal('addWarningModal')">⚠️ Tanbeh berish</button>
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

      <!-- Eng ko'p tanbeh olganlar -->
      <?php if (!empty($student_stats)): ?>
      <div class="data-table-wrapper fade-in" style="margin-bottom:20px; padding:20px">
        <h3 style="margin-bottom:14px; font-size:14px; color:var(--text-muted)">🔥 Eng ko'p tanbeh olgan o'quvchilar</h3>
        <div style="display:flex; gap:10px; flex-wrap:wrap">
          <?php foreach ($student_stats as $ss): ?>
          <div style="background:rgba(220,38,38,0.1); border:1px solid rgba(220,38,38,0.2); border-radius:10px; padding:12px 16px; min-width:160px">
            <div style="font-size:12px; color:var(--text-muted)">O'quvchi</div>
            <div style="font-weight:600; font-size:13px"><?= htmlspecialchars($ss['full_name']) ?></div>
            <div style="font-size:22px; font-weight:800; color:#f87171; margin-top:4px"><?= $ss['cnt'] ?> ta</div>
            <?php if ($ss['cnt'] >= 3): ?>
            <div style="font-size:11px; color:#fbbf24; margin-top:4px">🚨 Limit yetdi!</div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Tanbehllar jadvali -->
      <div class="data-table-wrapper fade-in">
        <div class="table-header">
          <h3>📋 Barcha Tanbehllar (<?= count($warnings) ?> ta)</h3>
        </div>
        <?php if (empty($warnings)): ?>
        <div class="empty-state">
          <div class="empty-icon">✅</div>
          <p>Hozircha hech qanday tanbeh yo'q</p>
        </div>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>O'quvchi</th>
              <th>Tanbeh turi</th>
              <th>Izoh</th>
              <th>O'qituvchi</th>
              <th>Sana</th>
              <th>O'quvchiga</th>
              <th>Ota-onaga</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($warnings as $i => $w): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><strong><?= htmlspecialchars($w['student_name']) ?></strong></td>
              <td><span class="badge badge-danger"><?= htmlspecialchars($w['type']) ?></span></td>
              <td style="max-width:200px"><?= htmlspecialchars($w['description']) ?></td>
              <td><?= htmlspecialchars($w['teacher_name']) ?></td>
              <td><?= date('d.m.Y H:i', strtotime($w['created_at'])) ?></td>
              <td>
                <?= $w['notified_student']
                    ? '<span class="badge badge-success">📱 ✅</span>'
                    : '<span class="badge badge-warning">⏳</span>'; ?>
              </td>
              <td>
                <?= $w['notified_parent']
                    ? '<span class="badge badge-success">👪 ✅</span>'
                    : '<span class="badge badge-warning">⏳</span>'; ?>
              </td>
              <td>
                <form method="POST" style="display:inline" onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
                  <input type="hidden" name="action" value="delete_warning">
                  <input type="hidden" name="warning_id" value="<?= $w['id'] ?>">
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

<!-- Modal: Tanbeh berish -->
<div class="modal-overlay" id="addWarningModal">
  <div class="modal">
    <div class="modal-header">
      <h3>⚠️ Tanbeh Berish</h3>
      <button class="modal-close" onclick="closeModal('addWarningModal')">✕</button>
    </div>
    <div style="background:rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.2); border-radius:10px; padding:12px; margin-bottom:20px; font-size:12px; color:#fca5a5">
      📌 Bu tanbeh faqat <strong>o'quvchi</strong> va uning <strong>ota-onasiga</strong> Telegram bot orqali yuboriladi. Boshqa hech kim ko'rmaydi.
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_warning">
      <div class="form-group">
        <label>O'quvchi *</label>
        <select name="student_id" required>
          <option value="">— O'quvchini tanlang —</option>
          <?php foreach ($students as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>O'qituvchi *</label>
        <select name="teacher_id" required>
          <option value="">— O'qituvchini tanlang —</option>
          <?php foreach ($teachers as $t): ?>
          <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Tanbeh turi *</label>
        <select name="type" required>
          <option value="">— Sababni tanlang —</option>
          <?php foreach ($warning_types as $wt): ?>
          <option value="<?= $wt ?>"><?= $wt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Batafsil izoh *</label>
        <textarea name="description" placeholder="Tanbeh sababi haqida batafsil yozing..." required></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#dc2626,#991b1b)">
        ⚠️ Tanbeh Berish & Xabar Yuborish
      </button>
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
