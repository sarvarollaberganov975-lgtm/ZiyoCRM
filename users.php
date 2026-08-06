<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

// Amal qilish
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $role      = $_POST['role'] ?? '';
        $phone     = trim($_POST['phone'] ?? '');

        if ($username && $password && $full_name && $role) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO users (username, password, full_name, role, phone) VALUES (?,?,?,?,?)")
                   ->execute([$username, $hash, $full_name, $role, $phone]);
                $msg = "✅ Foydalanuvchi muvaffaqiyatli qo'shildi!";
            } catch (PDOException $e) {
                $err = "❌ Bu username allaqachon mavjud!";
            }
        } else {
            $err = "❌ Barcha majburiy maydonlarni to'ldiring!";
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['user_id'];
        if ($id !== (int)$_SESSION['user_id']) {
            $db->prepare("UPDATE users SET is_active=0 WHERE id=?")->execute([$id]);
            $msg = "✅ Foydalanuvchi o'chirildi!";
        } else {
            $err = "❌ O'z akkauntingizni o'chira olmaysiz!";
        }
    }

    if ($action === 'delete_username') {
        $id = (int)$_POST['user_id'];
        // username ni o'chirib bo'lmaydi, lekin Telegram chat ID ni tozalash
        $new_username = 'user_' . $id . '_' . time();
        $db->prepare("UPDATE users SET username=?, telegram_chat_id=NULL WHERE id=?")->execute([$new_username, $id]);
        $msg = "✅ Foydalanuvchi username va Telegram ulanganligi o'chirildi!";
    }

    if ($action === 'update_telegram') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $chat_id = trim($_POST['telegram_chat_id'] ?? '');
        if ($user_id > 0) {
            $db->prepare("UPDATE users SET telegram_chat_id=? WHERE id=?")->execute([$chat_id ?: null, $user_id]);
            $msg = "✅ Telegram Chat ID muvaffaqiyatli saqlandi!";
        }
    }

    if ($action === 'link_parent') {
        $parent_id  = (int)$_POST['parent_id'];
        $student_id = (int)$_POST['student_id'];
        // Agar bog'liq bo'lmasa
        $exists = $db->prepare("SELECT id FROM parent_student WHERE parent_id=? AND student_id=?");
        $exists->execute([$parent_id, $student_id]);
        if (!$exists->fetch()) {
            $db->prepare("INSERT INTO parent_student (parent_id,student_id) VALUES (?,?)")
               ->execute([$parent_id, $student_id]);
            $msg = "✅ Ota-ona farzandiga bog'landi!";
        } else {
            $err = "⚠️ Bu bog'lanish allaqachon mavjud!";
        }
    }
}

$role_filter = $_GET['role'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$query = "SELECT * FROM users WHERE is_active=1";
$params = [];

if ($role_filter !== 'all') {
    $query .= " AND role=?";
    $params[] = $role_filter;
}
if ($search) {
    $query .= " AND (full_name LIKE ? OR username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$students = $db->query("SELECT id, full_name FROM users WHERE role='student' AND is_active=1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$parents  = $db->query("SELECT id, full_name FROM users WHERE role='parent' AND is_active=1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

$role_labels = ['admin'=>'👑 Admin','teacher'=>"👨‍🏫 O'qituvchi",'student'=>"🎓 O'quvchi",'parent'=>'👪 Ota-ona'];
$role_badges = ['admin'=>'badge-purple','teacher'=>'badge-primary','student'=>'badge-success','parent'=>'badge-warning'];
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Foydalanuvchilar — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">👥 Foydalanuvchilar</div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('addModal')">➕ Yangi qo'shish</button>
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>

      <!-- Ma'lumot qutisi: Ota-ona bog'lash nima? -->
      <div class="info-box fade-in" style="
        background: linear-gradient(135deg, rgba(124,58,237,0.15), rgba(59,130,246,0.10));
        border: 1.5px solid rgba(124,58,237,0.4);
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
      ">
        <span style="font-size:22px; margin-top:2px">ℹ️</span>
        <div>
          <strong style="color:#a78bfa; font-size:14px">"Ota-ona farzandiga bog'landi" — bu nima?</strong>
          <p style="margin:6px 0 0; font-size:13px; color:var(--text-muted); line-height:1.6">
            Bu funksiya ota-ona akkauntini o'quvchi (farzand) akkauntiga ulaydi. 
            Bog'langandan so'ng, o'quvchiga tegishli <b>barcha to'lov xabarlari, tanbehlar va e'lonlar</b> 
            ota-onaning Telegram akkauntiga ham yuboriladi. 
            Ya'ni, ota-ona farzandining o'quv jarayonini real vaqtda kuzatib borishi mumkin.
          </p>
        </div>
      </div>

      <!-- Ota-onani bog'lash -->
      <div class="data-table-wrapper fade-in" style="margin-bottom:20px; padding:20px;">
        <h3 style="margin-bottom:16px; font-size:15px">🔗 Ota-onani o'quvchiga bog'lash</h3>
        <form method="POST" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end">
          <input type="hidden" name="action" value="link_parent">
          <div class="form-group" style="margin:0; flex:1; min-width:180px">
            <label>Ota-ona</label>
            <select name="parent_id" required>
              <option value="">— tanlang —</option>
              <?php foreach ($parents as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0; flex:1; min-width:180px">
            <label>O'quvchi</label>
            <select name="student_id" required>
              <option value="">— tanlang —</option>
              <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="height:42px">🔗 Bog'lash</button>
        </form>
      </div>

      <!-- Filtr -->
      <div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap">
        <?php
        $filters = ['all'=>'Barchasi','admin'=>'Adminlar','teacher'=>"O'qituvchilar",'student'=>"O'quvchilar",'parent'=>'Ota-onalar'];
        foreach ($filters as $k=>$v):
        ?>
        <a href="?role=<?= $k ?>&search=<?= urlencode($search) ?>"
           class="btn btn-sm <?= $role_filter===$k?'btn-primary':'btn-outline' ?>"><?= $v ?></a>
        <?php endforeach; ?>

        <form method="GET" style="margin-left:auto; display:flex; gap:8px">
          <input type="hidden" name="role" value="<?= htmlspecialchars($role_filter) ?>">
          <input type="text" name="search" placeholder="Qidirish..." value="<?= htmlspecialchars($search) ?>"
                 style="background:rgba(255,255,255,0.06); border:1.5px solid var(--border); border-radius:8px; padding:7px 14px; color:var(--text); font-size:13px; outline:none">
          <button type="submit" class="btn btn-outline btn-sm">🔍</button>
        </form>
      </div>

      <!-- Jadval -->
      <div class="data-table-wrapper fade-in">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>To'liq ism</th>
              <th>Username</th>
              <th>Rol</th>
              <th>Telefon</th>
              <th>Telegram</th>
              <th>Qo'shildi</th>
              <th>Amallar</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
            <tr><td colspan="8" style="text-align:center; padding:40px; color:var(--text-muted)">Foydalanuvchi topilmadi</td></tr>
            <?php else: ?>
            <?php foreach ($users as $i => $u): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
              <td>
                <div style="display:flex; align-items:center; gap:6px">
                  <code style="color:#a78bfa"><?= htmlspecialchars($u['username']) ?></code>
                  <button type="button" 
                    class="btn btn-sm" 
                    style="padding:2px 7px; font-size:11px; background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); border-radius:5px; cursor:pointer"
                    onclick="openDeleteUsernameModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')"
                    title="Username va Telegram ulanishini o'chirish">
                    ✕
                  </button>
                </div>
              </td>
              <td><span class="badge <?= $role_badges[$u['role']] ?? 'badge-primary' ?>"><?= $role_labels[$u['role']] ?? $u['role'] ?></span></td>
              <td><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
              <td>
                <form method="POST" style="display:flex; align-items:center; gap:4px">
                  <input type="hidden" name="action" value="update_telegram">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="text" name="telegram_chat_id" value="<?= htmlspecialchars($u['telegram_chat_id'] ?? '') ?>" placeholder="Chat ID" style="width:90px; padding:3px 6px; font-size:11px; background:rgba(255,255,255,0.06); border:1px solid var(--border); border-radius:4px; color:var(--text)">
                  <button type="submit" class="btn btn-sm btn-outline" style="padding:2px 6px; font-size:11px;" title="Chat ID saqlash">💾</button>
                </form>
                <?php if ($u['telegram_chat_id']): ?>
                  <span style="font-size:10px; color:#10b981">✅ Ulangan</span>
                <?php else: ?>
                  <span style="font-size:10px; color:#f59e0b">⏳ Chat ID yo'q</span>
                <?php endif; ?>
              </td>
              <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
              <td>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Rostdan ham o\'chirmoqchimisiz?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Username o'chirish modali -->
      <div class="modal-overlay" id="deleteUsernameModal">
        <div class="modal" style="max-width:420px">
          <div class="modal-header">
            <h3>🗑️ Username o'chirish</h3>
            <button class="modal-close" onclick="closeModal('deleteUsernameModal')">✕</button>
          </div>
          <div style="padding:0 0 16px">
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:16px">
              <strong id="del_username_display" style="color:#a78bfa"></strong> — foydalanuvchining username va Telegram ulanishini o'chirmoqchimisiz?
              <br><br>
              Davom etish uchun parolni kiriting:
            </p>
            <div class="form-group">
              <label>Parol</label>
              <input type="password" id="del_username_pass" placeholder="Parolni kiriting..." autocomplete="off">
            </div>
            <p id="del_username_err" style="color:#f87171; font-size:13px; display:none">❌ Parol noto'g'ri!</p>
          </div>
          <form method="POST" id="deleteUsernameForm">
            <input type="hidden" name="action" value="delete_username">
            <input type="hidden" name="user_id" id="del_username_user_id" value="">
            <button type="button" class="btn btn-danger" onclick="confirmDeleteUsername()">🗑️ O'chirish</button>
            <button type="button" class="btn btn-outline" onclick="closeModal('deleteUsernameModal')" style="margin-left:8px">Bekor qilish</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Yangi foydalanuvchi -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Yangi Foydalanuvchi</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>To'liq ism *</label>
        <input type="text" name="full_name" placeholder="Ism Familiya" required>
      </div>
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" placeholder="foydalanuvchi_nomi" required>
      </div>
      <div class="form-group">
        <label>Parol *</label>
        <input type="password" name="password" placeholder="Kamida 6 belgi" required minlength="6">
      </div>
      <div class="form-group">
        <label>Rol *</label>
        <select name="role" required>
          <option value="">— tanlang —</option>
          <option value="teacher">👨‍🏫 O'qituvchi</option>
          <option value="student">🎓 O'quvchi</option>
          <option value="parent">👪 Ota-ona</option>
          <option value="admin">👑 Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label>Telefon raqami</label>
        <input type="text" name="phone" placeholder="+998 90 123 45 67">
      </div>
      <button type="submit" class="btn btn-primary">✅ Saqlash</button>
    </form>
  </div>
</div>

<script>
const DELETE_USERNAME_PASS = 'Zebo1998';

function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('show'); });
});

function openDeleteUsernameModal(userId, username) {
  document.getElementById('del_username_user_id').value = userId;
  document.getElementById('del_username_display').textContent = '@' + username;
  document.getElementById('del_username_pass').value = '';
  document.getElementById('del_username_err').style.display = 'none';
  openModal('deleteUsernameModal');
}

function confirmDeleteUsername() {
  const pass = document.getElementById('del_username_pass').value;
  const errEl = document.getElementById('del_username_err');
  if (pass.toLowerCase() !== 'zebo1998') {
    errEl.style.display = 'block';
    document.getElementById('del_username_pass').focus();
    return;
  }
  errEl.style.display = 'none';
  document.getElementById('deleteUsernameForm').submit();
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
