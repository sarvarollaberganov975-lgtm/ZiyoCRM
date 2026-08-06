<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['user_role'];
    $map = ['admin'=>'admin/dashboard.php','teacher'=>'teacher/dashboard.php','student'=>'student/dashboard.php','parent'=>'parent/dashboard.php'];
    header('Location: /' . ($map[$r] ?? 'index.php'));
    exit;
}

$error = '';
$role = $_POST['role'] ?? $_GET['role'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'admin';

    if ($username && $password) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username=? AND role=? AND is_active=1");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['username']  = $user['username'];
            $map = ['admin'=>'admin/dashboard.php','teacher'=>'teacher/dashboard.php','student'=>'student/dashboard.php','parent'=>'parent/dashboard.php'];
            header('Location: /' . $map[$role]);
            exit;
        } else {
            $error = "Login yoki parol noto'g'ri!";
        }
    } else {
        $error = "Barcha maydonlarni to'ldiring!";
    }
}

$roles = [
    'admin'   => ['icon'=>'👑','name'=>'Admin',        'color'=>'#8b5cf6','bg'=>'rgba(139,92,246,0.15)','desc'=>'Tizim boshqaruvi'],
    'teacher' => ['icon'=>'👨‍🏫','name'=>"O'qituvchi",'color'=>'#3b82f6','bg'=>'rgba(59,130,246,0.15)', 'desc'=>'Darslar va talabalar'],
    'student' => ['icon'=>'🎓','name'=>"O'quvchi",    'color'=>'#10b981','bg'=>'rgba(16,185,129,0.15)','desc'=>"O'z ma'lumotlarim"],
    'parent'  => ['icon'=>'👪','name'=>'Ota-ona',      'color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.15)','desc'=>'Farzand nazorati'],
];
?><!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="assets/logo.png">
<link rel="shortcut icon" href="assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ZiyoCRM — Kirish</title>
<meta name="description" content="ZiyoCRM ta'lim markazi boshqaruv tizimi">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}

body {
  font-family: 'Inter', sans-serif;
  background: #070b13;
  color: #e6edf3;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow-x: hidden;
  padding: 15px 0;
}

/* Animated background blobs */
.bg-blob {
  position: fixed;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.12;
  pointer-events: none;
}
.bg-blob-1 { width: 600px; height: 600px; background: #6366f1; top: -200px; left: -150px; animation: blobMove 12s ease-in-out infinite; }
.bg-blob-2 { width: 500px; height: 500px; background: #10b981; bottom: -150px; right: -100px; animation: blobMove 15s ease-in-out infinite reverse; }
.bg-blob-3 { width: 300px; height: 300px; background: #f59e0b; top: 50%; left: 50%; transform: translate(-50%,-50%); animation: blobMove 10s ease-in-out infinite 3s; }

@keyframes blobMove {
  0%,100% { transform: scale(1) translate(0,0); }
  33% { transform: scale(1.1) translate(30px,-20px); }
  66% { transform: scale(0.95) translate(-20px,30px); }
}

/* Grid pattern */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
}

/* Main container */
.login-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 440px;
  padding: 12px;
  margin: auto;
  animation: fadeUp 0.6s ease forwards;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Header branding */
.brand-header {
  text-align: center;
  margin-bottom: 14px;
}

.brand-logo-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
}

.brand-header p {
  font-size: 12px; color: #6b7a8d;
  line-height: 1.4;
  margin-top: 4px;
}

/* Glass card */
.glass-card {
  background: rgba(13,17,23,0.85);
  border: 1px solid rgba(240,246,252,0.08);
  border-radius: 20px;
  padding: 20px 22px;
  backdrop-filter: blur(20px);
  box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.05);
}

/* Section label */
.section-label {
  font-size: 10px; font-weight: 700;
  color: #6b7a8d;
  text-transform: uppercase; letter-spacing: 1.2px;
  margin-bottom: 12px;
}

/* Role cards grid */
.role-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 24px;
}

.role-card {
  border: 1.5px solid rgba(240,246,252,0.08);
  border-radius: 14px;
  padding: 14px 10px;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
  background: rgba(255,255,255,0.02);
  color: #6b7a8d;
  position: relative;
  overflow: hidden;
  user-select: none;
}

.role-card::after {
  content: '';
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity 0.25s;
  border-radius: 12px;
}

.role-card:hover {
  transform: translateY(-3px);
  border-color: rgba(255,255,255,0.15);
  color: #c9d1d9;
  background: rgba(255,255,255,0.05);
}

.role-card.active {
  transform: translateY(-2px);
  color: #fff;
}

.role-card.active-admin  { border-color: #8b5cf6; background: rgba(139,92,246,0.13); box-shadow: 0 4px 20px rgba(139,92,246,0.25); }
.role-card.active-teacher{ border-color: #3b82f6; background: rgba(59,130,246,0.13); box-shadow: 0 4px 20px rgba(59,130,246,0.25); }
.role-card.active-student{ border-color: #10b981; background: rgba(16,185,129,0.13); box-shadow: 0 4px 20px rgba(16,185,129,0.25); }
.role-card.active-parent { border-color: #f59e0b; background: rgba(245,158,11,0.13); box-shadow: 0 4px 20px rgba(245,158,11,0.25); }

.rc-emoji { font-size: 30px; margin-bottom: 6px; display: block; }
.rc-name { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.rc-desc { font-size: 10px; margin-top: 3px; opacity: 0.7; }

/* Divider */
.divider {
  height: 1px;
  background: rgba(240,246,252,0.06);
  margin: 20px 0;
}

/* Form title */
.form-title {
  font-size: 15px; font-weight: 700;
  margin-bottom: 18px;
  display: flex; align-items: center; gap: 8px;
  color: #e6edf3;
}

/* Alert */
.alert {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 14px; border-radius: 10px;
  font-size: 13px; margin-bottom: 16px;
}
.alert-danger { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }

/* Form groups */
.form-group { margin-bottom: 14px; }

.form-group label {
  display: block;
  font-size: 11px; font-weight: 700;
  color: #6b7a8d;
  text-transform: uppercase; letter-spacing: 0.8px;
  margin-bottom: 7px;
}

.input-wrap { position: relative; }

.input-icon {
  position: absolute; left: 13px; top: 50%;
  transform: translateY(-50%);
  font-size: 16px; pointer-events: none;
  opacity: 0.6;
}

.form-group input {
  width: 100%;
  background: rgba(255,255,255,0.04);
  border: 1.5px solid rgba(240,246,252,0.08);
  border-radius: 11px;
  padding: 13px 14px 13px 42px;
  color: #e6edf3;
  font-size: 14px;
  font-family: 'Inter', sans-serif;
  transition: all 0.2s;
  outline: none;
}

.form-group input::placeholder { color: #4a5568; }

.form-group input:focus {
  border-color: #6366f1;
  background: rgba(99,102,241,0.07);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}

/* Dynamic border color on focus by role */
body.role-admin  .form-group input:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.15); }
body.role-teacher .form-group input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
body.role-student .form-group input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); }
body.role-parent  .form-group input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }

/* Password toggle */
.pwd-toggle {
  position: absolute; right: 13px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: #6b7a8d; font-size: 16px; padding: 2px;
  transition: color 0.2s;
  line-height: 1;
}
.pwd-toggle:hover { color: #c9d1d9; }

/* Login button */
.btn-login {
  width: 100%; padding: 14px;
  border: none; border-radius: 12px;
  font-size: 15px; font-weight: 700;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
  display: flex; align-items: center; justify-content: center; gap: 8px;
  position: relative; overflow: hidden;
  color: #fff;
  margin-top: 6px;
}

.btn-login::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(rgba(255,255,255,0.1), transparent);
  opacity: 0; transition: opacity 0.2s;
}

.btn-login:hover::before { opacity: 1; }
.btn-login:hover { transform: translateY(-2px); }
.btn-login:active { transform: translateY(0); }

.btn-login.btn-admin   { background: linear-gradient(135deg, #7c3aed, #6d28d9); box-shadow: 0 4px 20px rgba(109,40,217,0.4); }
.btn-login.btn-teacher { background: linear-gradient(135deg, #2563eb, #1d4ed8); box-shadow: 0 4px 20px rgba(29,78,216,0.4); }
.btn-login.btn-student { background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 20px rgba(4,120,87,0.4); }
.btn-login.btn-parent  { background: linear-gradient(135deg, #d97706, #b45309); box-shadow: 0 4px 20px rgba(180,83,9,0.4); }

.btn-login:hover.btn-admin   { box-shadow: 0 8px 30px rgba(109,40,217,0.55); }
.btn-login:hover.btn-teacher { box-shadow: 0 8px 30px rgba(29,78,216,0.55); }
.btn-login:hover.btn-student { box-shadow: 0 8px 30px rgba(4,120,87,0.55); }
.btn-login:hover.btn-parent  { box-shadow: 0 8px 30px rgba(180,83,9,0.55); }

/* Hint */
.hint-box {
  margin-top: 14px;
  padding: 12px 14px;
  background: rgba(99,102,241,0.06);
  border: 1px solid rgba(99,102,241,0.12);
  border-radius: 10px;
  font-size: 11px; color: #6b7a8d;
  line-height: 1.8;
  display: flex; align-items: flex-start; gap: 8px;
}

.hint-box code {
  color: #a5b4fc;
  background: rgba(99,102,241,0.15);
  padding: 1px 6px; border-radius: 4px;
  font-family: monospace;
}

/* Footer */
.login-footer {
  text-align: center;
  margin-top: 20px;
  font-size: 11px; color: #4a5568;
}

/* Responsive */
@media(max-width:480px) {
  .glass-card { padding: 22px 18px; border-radius: 18px; }
  .role-grid { gap: 8px; }
  .rc-emoji { font-size: 24px; }
}
</style>
</head>
<body class="role-<?= $role ?>">

<!-- Background decorations -->
<div class="bg-blob bg-blob-1"></div>
<div class="bg-blob bg-blob-2"></div>
<div class="bg-blob bg-blob-3"></div>

<div class="login-container">

  <div style="position: absolute; top: -10px; right: 10px; z-index: 20;">
    <button onclick="toggleTheme()" class="theme-toggle-btn">☀️ Kun rejimi</button>
  </div>

  <!-- Brand header -->
  <div class="brand-header">
    <div class="brand-logo-wrap" style="display: inline-flex; align-items: center; justify-content: center; padding: 2px;">
      <img src="assets/ziyo_clean_icon.png" alt="ZiyoCRM Logo" style="height: 80px; width: auto; object-fit: contain; filter: drop-shadow(0 6px 20px rgba(0,162,255,0.4));">
    </div>
    <p style="margin-top: 4px;">Rolingizni tanlang va tizimga kiring</p>
  </div>

  <!-- Glass card -->
  <div class="glass-card">

    <!-- Role selector -->
    <div id="roleSelectorWrap">
      <div class="section-label">Rolni tanlang</div>
      <div class="role-grid">
        <?php foreach($roles as $key => $r): ?>
        <div class="role-card <?= $role===$key ? 'active active-'.$key : '' ?>"
             onclick="selectRole('<?= $key ?>')"
             id="rc-<?= $key ?>">
          <span class="rc-emoji"><?= $r['icon'] ?></span>
          <div class="rc-name"><?= $r['name'] ?></div>
          <div class="rc-desc"><?= $r['desc'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="divider"></div>
    </div>

    <!-- Login form -->
    <div class="form-title" id="formTitle">
      <?= $roles[$role]['icon'] ?> <?= $roles[$role]['name'] ?> sifatida kirish
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error']==='access_denied'): ?>
    <div class="alert alert-danger">⛔ Bu sahifaga kirish ruxsati yo'q!</div>
    <?php endif; ?>

    <form method="POST" action="index.php" id="loginForm">
      <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($role) ?>">

      <div class="form-group">
        <label for="username">Foydalanuvchi nomi</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" name="username" id="username"
                 placeholder="username kiriting..."
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 autocomplete="username" required>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Parol</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" name="password" id="password"
                 placeholder="Parolni kiriting..."
                 autocomplete="current-password" required>
          <button type="button" class="pwd-toggle" onclick="togglePwd()" id="pwdToggle" title="Parolni ko'rsatish">
            👁️
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login btn-<?= $role ?>" id="loginBtn">
        <span id="btnIcon"><?= $roles[$role]['icon'] ?></span>
        <span id="btnText">Kirish</span>
      </button>
    </form>

  </div>

  <div class="login-footer">
    © 2026 ZiyoCRM · Barcha huquqlar himoyalangan
  </div>

</div>

<script>
const roleData = {
  admin:   { icon:'👑',   name:'Admin',        cls:'btn-admin',   ac:'active-admin' },
  teacher: { icon:'👨‍🏫', name:"O'qituvchi",  cls:'btn-teacher', ac:'active-teacher' },
  student: { icon:'🎓',   name:"O'quvchi",     cls:'btn-student', ac:'active-student' },
  parent:  { icon:'👪',   name:'Ota-ona',       cls:'btn-parent',  ac:'active-parent' },
};

function selectRole(r) {
  // Update role cards
  document.querySelectorAll('.role-card').forEach(c => {
    c.className = 'role-card';
  });
  const card = document.getElementById('rc-' + r);
  card.className = 'role-card active ' + roleData[r].ac;

  // Update hidden input
  document.getElementById('roleInput').value = r;

  // Update form title
  document.getElementById('formTitle').innerHTML =
    roleData[r].icon + ' ' + roleData[r].name + ' sifatida kirish';

  // Update button
  const btn = document.getElementById('loginBtn');
  btn.className = 'btn-login ' + roleData[r].cls;
  document.getElementById('btnIcon').textContent = roleData[r].icon;

  // Update body class for focus ring colors
  document.body.className = 'role-' + r;

  // Small bounce animation on card
  card.style.transform = 'scale(0.97) translateY(-2px)';
  setTimeout(() => { card.style.transform = ''; }, 150);
}

// Password toggle
let pwdVisible = false;
function togglePwd() {
  pwdVisible = !pwdVisible;
  const inp = document.getElementById('password');
  const btn = document.getElementById('pwdToggle');
  inp.type = pwdVisible ? 'text' : 'password';
  btn.textContent = pwdVisible ? '🙈' : '👁️';
}

// Form submit loading state
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  document.getElementById('btnText').textContent = 'Kirilmoqda...';
  btn.style.opacity = '0.8';
  btn.disabled = true;
});

// Enter key focus management
document.getElementById('username').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('password').focus();
  }
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
