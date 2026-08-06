<?php
require_once '../includes/config.php';
requireLogin('teacher');
$db   = getDB();
$user = getCurrentUser();

$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user_dash') {
    $id = (int)($_POST['user_id'] ?? 0);
    $my_id = (int)($_SESSION['user_id'] ?? 0);
    if ($id > 0 && $id !== $my_id) {
        $db->prepare("UPDATE users SET is_active=0 WHERE id=?")->execute([$id]);
        $msg = "✅ Foydalanuvchi muvaffaqiyatli o'chirildi!";
    } elseif ($id === $my_id) {
        $err = "❌ O'z akkauntingizni o'chira olmaysiz!";
    } else {
        $err = "❌ Xato! Foydalanuvchi topilmadi.";
    }
}

$stats = [
    'students'        => $db->query("SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1")->fetchColumn(),
    'teachers'        => $db->query("SELECT COUNT(*) FROM users WHERE role='teacher' AND is_active=1")->fetchColumn(),
    'parents'         => $db->query("SELECT COUNT(*) FROM users WHERE role='parent' AND is_active=1")->fetchColumn(),
    'groups'          => $db->query("SELECT COUNT(*) FROM groups")->fetchColumn(),
    'warnings'        => $db->query("SELECT COUNT(*) FROM warnings")->fetchColumn(),
    'payments_pending'=> $db->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn(),
];

// Oylik tushum (so'nggi 6 oy)
$monthly_income = [];
$monthly_labels = [];
for ($i = 5; $i >= 0; $i--) {
    $month_ts    = strtotime("-$i months");
    $month_str   = date('Y-m', $month_ts);
    $month_label = date('M Y', $month_ts);
    $amount = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND strftime('%Y-%m', created_at)=?");
    $amount->execute([$month_str]);
    $monthly_income[]  = (float)$amount->fetchColumn();
    $monthly_labels[]  = $month_label;
}

// Guruhlar statistikasi
$groups_chart = $db->query("
    SELECT g.name, COUNT(sg.student_id) as cnt
    FROM groups g LEFT JOIN student_groups sg ON g.id=sg.group_id
    GROUP BY g.id ORDER BY cnt DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// To'lov holatlari (donut uchun)
$pay_paid    = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
$pay_pending = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='pending'")->fetchColumn();
$pay_overdue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='overdue'")->fetchColumn();
?><!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Panel — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.nav-item.active{background:rgba(124,58,237,0.2);color:#c4b5fd;}
.sidebar .logo-icon{background:linear-gradient(135deg,#7c3aed,#6d28d9);}
.sidebar .user-avatar{background:linear-gradient(135deg,#7c3aed,#6d28d9);}
.role-tag{font-size:10px;padding:2px 8px;border-radius:20px;font-weight:600;}
.role-tag.admin{background:rgba(139,92,246,0.2);color:#c4b5fd;}
.role-tag.teacher{background:rgba(59,130,246,0.2);color:#93c5fd;}
.role-tag.student{background:rgba(16,185,129,0.2);color:#6ee7b7;}
.role-tag.parent{background:rgba(245,158,11,0.2);color:#fcd34d;}
.quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;}
.qa-card{background:#161b22;border:1px solid rgba(240,246,252,0.08);border-radius:14px;padding:18px;text-align:center;text-decoration:none;color:#e6edf3;transition:all 0.25s;}
.qa-card:hover{transform:translateY(-3px);border-color:rgba(99,102,241,0.4);}
.qa-card .qa-icon{font-size:26px;margin-bottom:8px;}
.qa-card .qa-label{font-size:12px;font-weight:600;color:#8b949e;}
.chart-card{background:#161b22;border:1px solid rgba(240,246,252,0.08);border-radius:14px;padding:20px;}
.chart-title{font-size:13px;font-weight:700;color:#e6edf3;margin:0 0 16px;display:flex;align-items:center;gap:8px;}
.chart-subtitle{font-size:11px;color:#8b949e;margin-left:auto;}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-title">📊 Bosh sahifa</div>
      <div class="topbar-right">
        <span style="font-size:12px;color:#8b949e">👑 <?= htmlspecialchars($user['full_name']) ?></span>
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($msg): ?><div class="alert alert-success" style="margin-bottom:16px"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger" style="margin-bottom:16px"><?= $err ?></div><?php endif; ?>
      <!-- Stats -->
      <div class="stats-grid fade-in">
        <div class="stat-card">
          <div class="sc-glow" style="background:#10b981;"></div>
          <div class="sc-icon">🎓</div>
          <div class="sc-value" style="color:#6ee7b7"><?= $stats['students'] ?></div>
          <div class="sc-label">O'quvchilar</div>
        </div>
        <div class="stat-card">
          <div class="sc-glow" style="background:#3b82f6;"></div>
          <div class="sc-icon">👨‍🏫</div>
          <div class="sc-value" style="color:#93c5fd"><?= $stats['teachers'] ?></div>
          <div class="sc-label">O'qituvchilar</div>
        </div>
        <div class="stat-card">
          <div class="sc-glow" style="background:#f59e0b;"></div>
          <div class="sc-icon">👪</div>
          <div class="sc-value" style="color:#fcd34d"><?= $stats['parents'] ?></div>
          <div class="sc-label">Ota-onalar</div>
        </div>
        <div class="stat-card">
          <div class="sc-glow" style="background:#8b5cf6;"></div>
          <div class="sc-icon">📚</div>
          <div class="sc-value" style="color:#c4b5fd"><?= $stats['groups'] ?></div>
          <div class="sc-label">Guruhlar</div>
        </div>
        <div class="stat-card">
          <div class="sc-glow" style="background:#ef4444;"></div>
          <div class="sc-icon">⚠️</div>
          <div class="sc-value" style="color:#fca5a5"><?= $stats['warnings'] ?></div>
          <div class="sc-label">Tanbehllar</div>
        </div>
        <div class="stat-card">
          <div class="sc-glow" style="background:#f59e0b;"></div>
          <div class="sc-icon">💳</div>
          <div class="sc-value" style="color:#fcd34d"><?= $stats['payments_pending'] ?></div>
          <div class="sc-label">To'lov kutilmoqda</div>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="quick-actions fade-in">
        <a href="users.php" class="qa-card">
          <div class="qa-icon">👥</div>
          <div class="qa-label">Foydalanuvchilar</div>
        </a>
        <a href="warnings.php" class="qa-card">
          <div class="qa-icon">⚠️</div>
          <div class="qa-label">Tanbehllar</div>
        </a>
        <a href="attendance.php" class="qa-card">
          <div class="qa-icon">📋</div>
          <div class="qa-label">Davomat</div>
        </a>
        <a href="telegram.php" class="qa-card">
          <div class="qa-icon">📱</div>
          <div class="qa-label">Telegram Bot</div>
        </a>
      </div>

      <!-- Grafikalar -->
      <!-- 1-qator: Oylik tushum (kengroq) + To'lov holatlari (torroq) -->
      <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">

        <!-- Oylik tushum grafigi -->
        <div class="chart-card fade-in">
          <div class="chart-title">
            📈 Oylik Tushum Grafigi
            <span class="chart-subtitle">So'nggi 6 oy</span>
          </div>
          <canvas id="incomeChart" height="110"></canvas>
        </div>

        <!-- To'lov holatlari donut -->
        <div class="chart-card fade-in">
          <div class="chart-title">💰 To'lov Holatlari</div>
          <canvas id="payDonut" height="140"></canvas>
          <div style="display:flex;flex-direction:column;gap:6px;margin-top:12px;">
            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#8b949e">
              <span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block"></span>
              To'langan: <b style="color:#e6edf3"><?= number_format($pay_paid,0,'.',' ') ?> so'm</b>
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#8b949e">
              <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
              Kutilmoqda: <b style="color:#e6edf3"><?= number_format($pay_pending,0,'.',' ') ?> so'm</b>
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#8b949e">
              <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block"></span>
              Muddati o'tgan: <b style="color:#e6edf3"><?= number_format($pay_overdue,0,'.',' ') ?> so'm</b>
            </div>
          </div>
        </div>
      </div>

      <!-- 2-qator: Guruhlar bar chart (to'liq kenglik) -->
      <div class="chart-card fade-in" style="margin-bottom:20px;">
        <div class="chart-title">
          📚 Guruhlardagi O'quvchilar Soni
          <span class="chart-subtitle">Barcha guruhlar</span>
        </div>
        <?php if(empty($groups_chart)): ?>
        <div style="text-align:center;padding:40px;color:#8b949e">Hali guruh yo'q</div>
        <?php else: ?>
        <canvas id="groupsChart" height="70"></canvas>
        <?php endif; ?>
      </div>
    </div><!-- /page-content -->
  </div><!-- /main-content -->
</div>

<!-- Chart.js Skriptlar -->
<script>
// Ranglar
const COLORS = {
  purple: '#8b5cf6', blue: '#3b82f6', green: '#10b981',
  yellow: '#f59e0b', red: '#ef4444', cyan: '#06b6d4'
};
Chart.defaults.color = '#8b949e';
Chart.defaults.borderColor = 'rgba(240,246,252,0.08)';
Chart.defaults.font.family = "'Inter', sans-serif";

// 1. Oylik tushum — Line Chart
const incomeCtx = document.getElementById('incomeChart');
if (incomeCtx) {
  new Chart(incomeCtx, {
    type: 'line',
    data: {
      labels: <?= json_encode($monthly_labels) ?>,
      datasets: [{
        label: "Tushum (so'm)",
        data: <?= json_encode($monthly_income) ?>,
        borderColor: COLORS.purple,
        backgroundColor: 'rgba(139,92,246,0.12)',
        borderWidth: 2.5,
        pointBackgroundColor: COLORS.purple,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5,
        tension: 0.4,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1c2333',
          borderColor: 'rgba(139,92,246,0.4)',
          borderWidth: 1,
          callbacks: {
            label: ctx => ' ' + ctx.parsed.y.toLocaleString('uz') + " so'm"
          }
        }
      },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' } },
        y: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { callback: v => (v/1000).toFixed(0) + 'K' },
          beginAtZero: true
        }
      }
    }
  });
}

// 2. Guruhlar — Bar Chart
const groupsCtx = document.getElementById('groupsChart');
if (groupsCtx) {
  const groupLabels = <?= json_encode(array_column($groups_chart, 'name')) ?>;
  const groupData   = <?= json_encode(array_map('intval', array_column($groups_chart, 'cnt'))) ?>;
  const barColors   = ['#8b5cf6','#3b82f6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899','#84cc16'];
  new Chart(groupsCtx, {
    type: 'bar',
    data: {
      labels: groupLabels,
      datasets: [{
        label: "O'quvchilar soni",
        data: groupData,
        backgroundColor: barColors.map(c => c + '99'),
        borderColor: barColors,
        borderWidth: 2,
        borderRadius: 8,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1c2333',
          borderColor: 'rgba(99,102,241,0.4)',
          borderWidth: 1,
          callbacks: { label: ctx => ' ' + ctx.parsed.y + " ta o'quvchi" }
        }
      },
      scales: {
        x: { grid: { display: false } },
        y: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { stepSize: 1 },
          beginAtZero: true
        }
      }
    }
  });
}

// 3. To'lov holatlari — Doughnut
const donutCtx = document.getElementById('payDonut');
if (donutCtx) {
  const total = <?= $pay_paid + $pay_pending + $pay_overdue ?>;
  new Chart(donutCtx, {
    type: 'doughnut',
    data: {
      labels: ["To'langan", "Kutilmoqda", "Muddati o'tgan"],
      datasets: [{
        data: [<?= $pay_paid ?>, <?= $pay_pending ?>, <?= $pay_overdue ?>],
        backgroundColor: ['rgba(16,185,129,0.8)','rgba(245,158,11,0.8)','rgba(239,68,68,0.8)'],
        borderColor: ['#10b981','#f59e0b','#ef4444'],
        borderWidth: 2,
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1c2333',
          borderColor: 'rgba(99,102,241,0.4)',
          borderWidth: 1,
          callbacks: {
            label: ctx => ' ' + ctx.parsed.toLocaleString('uz') + " so'm"
          }
        }
      }
    }
  });
}
</script>

<!-- O'chirish modali -->
<div id="dashDeleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#161b22;border:1.5px solid rgba(239,68,68,0.4);border-radius:16px;padding:28px 32px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.5);">
    <h3 style="margin:0 0 16px;color:#f87171;font-size:16px;">🗑️ Foydalanuvchini o'chirish</h3>
    <p style="color:#8b949e;font-size:13px;margin-bottom:16px;line-height:1.6;">
      <strong id="dash_del_name" style="color:#e6edf3;"></strong> — foydalanuvchini o'chirmoqchimisiz?
      <br><br>
      ⚠️ Davom etish uchun <b>parolni</b> kiriting:
    </p>
    <div style="margin-bottom:12px;">
      <label style="font-size:12px;color:#8b949e;display:block;margin-bottom:6px;">Parol</label>
      <input type="password" id="dash_del_pass"
        placeholder="Parolni kiriting..."
        autocomplete="off"
        style="width:100%;box-sizing:border-box;background:rgba(255,255,255,0.05);border:1.5px solid rgba(255,255,255,0.1);border-radius:8px;padding:10px 14px;color:#e6edf3;font-size:14px;outline:none;"
      >
    </div>
    <p id="dash_del_err" style="color:#f87171;font-size:13px;margin:0 0 16px;display:none;">❌ Parol noto'g'ri!</p>
    <form method="POST" action="dashboard.php" id="dashDeleteForm" style="display:flex;gap:10px;">
      <input type="hidden" name="action" value="delete_user_dash">
      <input type="hidden" name="user_id" id="dash_del_uid" value="">
      <button type="submit"
        style="flex:1;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;border:none;border-radius:8px;padding:10px;font-size:14px;font-weight:600;cursor:pointer;"
        id="dash_del_submit_btn">
        🗑️ O'chirish
      </button>
      <button type="button" onclick="closeDashDeleteModal()"
        style="flex:1;background:rgba(255,255,255,0.06);color:#8b949e;border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:10px;font-size:14px;cursor:pointer;">
        Bekor qilish
      </button>
    </form>
  </div>
</div>

<script>
const DASH_DEL_PASS = 'Zebo1988';

function openDashDeleteModal(uid, name) {
  document.getElementById('dash_del_uid').value = uid;
  document.getElementById('dash_del_name').textContent = name;
  document.getElementById('dash_del_pass').value = '';
  document.getElementById('dash_del_err').style.display = 'none';
  // Submit tugmani disable qilib qo'yamiz — parol tasdiqlanganda enable bo'ladi
  document.getElementById('dash_del_submit_btn').disabled = true;
  document.getElementById('dash_del_submit_btn').style.opacity = '0.5';
  document.getElementById('dashDeleteModal').style.display = 'flex';
  setTimeout(() => document.getElementById('dash_del_pass').focus(), 150);
}

function closeDashDeleteModal() {
  document.getElementById('dashDeleteModal').style.display = 'none';
}

function checkPassInput() {
  const pass = document.getElementById('dash_del_pass').value;
  const btn = document.getElementById('dash_del_submit_btn');
  const errEl = document.getElementById('dash_del_err');
  if (pass === DASH_DEL_PASS) {
    btn.disabled = false;
    btn.style.opacity = '1';
    errEl.style.display = 'none';
  } else {
    btn.disabled = true;
    btn.style.opacity = '0.5';
  }
}

function confirmDashDelete() {
  const pass = document.getElementById('dash_del_pass').value;
  const errEl = document.getElementById('dash_del_err');
  if (pass !== DASH_DEL_PASS) {
    errEl.style.display = 'block';
    document.getElementById('dash_del_pass').focus();
    return false;
  }
  errEl.style.display = 'none';
  document.getElementById('dashDeleteForm').submit();
  return true;
}

document.getElementById('dashDeleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeDashDeleteModal();
});

document.getElementById('dash_del_pass').addEventListener('input', checkPassInput);

document.getElementById('dash_del_pass').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') confirmDashDelete();
});

document.getElementById('dashDeleteForm').addEventListener('submit', function(e) {
  const pass = document.getElementById('dash_del_pass').value;
  if (pass !== DASH_DEL_PASS) {
    e.preventDefault();
    document.getElementById('dash_del_err').style.display = 'block';
    document.getElementById('dash_del_pass').focus();
    return false;
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
