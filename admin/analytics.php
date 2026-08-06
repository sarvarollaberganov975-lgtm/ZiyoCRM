<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();

// ── 1. Oylik tushum (12 oy) ──
$monthly_income = []; $monthly_labels = [];
for ($i = 11; $i >= 0; $i--) {
    $ms = date('Y-m', strtotime("-$i months"));
    $ml = date('M Y', strtotime("-$i months"));
    $q  = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND strftime('%Y-%m', created_at)=?");
    $q->execute([$ms]);
    $monthly_income[] = (float)$q->fetchColumn();
    $monthly_labels[] = $ml;
}

// ── 2. To'lov holatlari ──
$pay_paid    = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
$pay_pending = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='pending'")->fetchColumn();
$pay_overdue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='overdue'")->fetchColumn();
$pay_total   = $pay_paid + $pay_pending + $pay_overdue;

// ── 3. Guruhlar statistikasi ──
$groups_data = $db->query("
    SELECT g.name, COUNT(sg.student_id) as cnt
    FROM groups g LEFT JOIN student_groups sg ON g.id=sg.group_id
    GROUP BY g.id ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── 4. Foydalanuvchilar rollari ──
$roles = $db->query("
    SELECT role, COUNT(*) as cnt FROM users WHERE is_active=1 GROUP BY role
")->fetchAll(PDO::FETCH_ASSOC);
$role_labels = ['admin'=>'👑 Admin','teacher'=>"O'qituvchi",'student'=>"O'quvchi",'parent'=>'Ota-ona'];
$role_colors_map = ['admin'=>'#8b5cf6','teacher'=>'#3b82f6','student'=>'#10b981','parent'=>'#f59e0b'];

// ── 5. Haftalik to'lovlar (so'nggi 7 kun) ──
$weekly_pay = []; $weekly_days = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $dl  = date('d-M', strtotime("-$i days"));
    $q   = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND strftime('%Y-%m-%d', created_at)=?");
    $q->execute([$day]);
    $weekly_pay[]  = (float)$q->fetchColumn();
    $weekly_days[] = $dl;
}

// ── 6. Umumiy statistikalar ──
$total_students  = $db->query("SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1")->fetchColumn();
$total_teachers  = $db->query("SELECT COUNT(*) FROM users WHERE role='teacher' AND is_active=1")->fetchColumn();
$total_groups    = $db->query("SELECT COUNT(*) FROM groups")->fetchColumn();
$total_warnings  = $db->query("SELECT COUNT(*) FROM warnings")->fetchColumn();
$total_payments  = $db->query("SELECT COUNT(*) FROM payments")->fetchColumn();
$avg_payment     = $db->query("SELECT COALESCE(AVG(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Grafika & Tahlil — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.nav-item.active{background:rgba(124,58,237,0.2);color:#c4b5fd;}
.sidebar .logo-icon{background:linear-gradient(135deg,#7c3aed,#6d28d9);}
.sidebar .user-avatar{background:linear-gradient(135deg,#7c3aed,#6d28d9);}
.chart-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.chart-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;}
.chart-card{background:#161b22;border:1px solid rgba(240,246,252,0.08);border-radius:16px;padding:22px;}
.chart-title{font-size:13px;font-weight:700;color:#e6edf3;margin:0 0 18px;display:flex;align-items:center;gap:8px;}
.chart-subtitle{font-size:11px;color:#8b949e;margin-left:auto;font-weight:400;}
.kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:24px;}
.kpi-card{background:#161b22;border:1px solid rgba(240,246,252,0.08);border-radius:14px;padding:16px;text-align:center;}
.kpi-val{font-size:22px;font-weight:800;color:#e6edf3;margin:6px 0 4px;}
.kpi-lbl{font-size:11px;color:#8b949e;font-weight:500;}
.kpi-icon{font-size:20px;}
.legend-row{display:flex;align-items:center;gap:8px;font-size:12px;color:#8b949e;margin-top:8px;}
.legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.section-heading{font-size:15px;font-weight:700;color:#e6edf3;margin:0 0 16px;padding-bottom:10px;border-bottom:1px solid rgba(240,246,252,0.08);}
</style>
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📈 Grafika & Tahlil</div>
      <div class="topbar-right">
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">

      <!-- KPI Kartalar -->
      <div class="section-heading">📊 Umumiy Ko'rsatkichlar</div>
      <div class="kpi-grid fade-in">
        <div class="kpi-card">
          <div class="kpi-icon">🎓</div>
          <div class="kpi-val" style="color:#6ee7b7"><?= $total_students ?></div>
          <div class="kpi-lbl">O'quvchilar</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon">👨‍🏫</div>
          <div class="kpi-val" style="color:#93c5fd"><?= $total_teachers ?></div>
          <div class="kpi-lbl">O'qituvchilar</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon">📚</div>
          <div class="kpi-val" style="color:#c4b5fd"><?= $total_groups ?></div>
          <div class="kpi-lbl">Guruhlar</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon">⚠️</div>
          <div class="kpi-val" style="color:#fca5a5"><?= $total_warnings ?></div>
          <div class="kpi-lbl">Tanbehllar</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon">✅</div>
          <div class="kpi-val" style="color:#6ee7b7"><?= number_format($pay_paid/1000,0,'.',',') ?>K</div>
          <div class="kpi-lbl">To'langan (so'm)</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon">📊</div>
          <div class="kpi-val" style="color:#fcd34d"><?= number_format($avg_payment/1000,0,'.',',') ?>K</div>
          <div class="kpi-lbl">O'rtacha to'lov</div>
        </div>
      </div>

      <!-- 1-qator: Oylik tushum (katta) -->
      <div class="section-heading">💰 Moliyaviy Tahlil</div>
      <div class="chart-card fade-in" style="margin-bottom:20px;">
        <div class="chart-title">
          📈 12 Oylik Tushum Dinamikasi
          <span class="chart-subtitle">So'nggi 12 oy</span>
        </div>
        <canvas id="incomeChart12" height="80"></canvas>
      </div>

      <!-- 2-qator: Haftalik + Donut -->
      <div class="chart-grid-2">
        <div class="chart-card fade-in">
          <div class="chart-title">
            📅 Haftalik To'lovlar
            <span class="chart-subtitle">So'nggi 7 kun</span>
          </div>
          <canvas id="weeklyChart" height="130"></canvas>
        </div>
        <div class="chart-card fade-in">
          <div class="chart-title">🥧 To'lov Holatlari Ulushi</div>
          <div style="max-width:200px;margin:0 auto;">
            <canvas id="payDonut" height="200"></canvas>
          </div>
          <div style="margin-top:14px;">
            <div class="legend-row">
              <span class="legend-dot" style="background:#10b981"></span>
              To'langan &nbsp;<b style="color:#e6edf3"><?= number_format($pay_paid,0,'.',' ') ?> so'm</b>
              <span style="margin-left:auto;color:#10b981"><?= $pay_total > 0 ? round($pay_paid/$pay_total*100) : 0 ?>%</span>
            </div>
            <div class="legend-row">
              <span class="legend-dot" style="background:#f59e0b"></span>
              Kutilmoqda &nbsp;<b style="color:#e6edf3"><?= number_format($pay_pending,0,'.',' ') ?> so'm</b>
              <span style="margin-left:auto;color:#f59e0b"><?= $pay_total > 0 ? round($pay_pending/$pay_total*100) : 0 ?>%</span>
            </div>
            <div class="legend-row">
              <span class="legend-dot" style="background:#ef4444"></span>
              Muddati o'tgan &nbsp;<b style="color:#e6edf3"><?= number_format($pay_overdue,0,'.',' ') ?> so'm</b>
              <span style="margin-left:auto;color:#ef4444"><?= $pay_total > 0 ? round($pay_overdue/$pay_total*100) : 0 ?>%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 3-qator: Guruhlar + Foydalanuvchilar -->
      <div class="section-heading">👥 Foydalanuvchilar & Guruhlar</div>
      <div class="chart-grid-2">
        <div class="chart-card fade-in">
          <div class="chart-title">
            📚 Guruhlar O'quvchilar Soni
            <span class="chart-subtitle">Guruh bo'yicha</span>
          </div>
          <?php if(empty($groups_data)): ?>
          <div style="text-align:center;padding:40px;color:#8b949e">Hali guruh yo'q</div>
          <?php else: ?>
          <canvas id="groupsChart" height="130"></canvas>
          <?php endif; ?>
        </div>
        <div class="chart-card fade-in">
          <div class="chart-title">🧩 Foydalanuvchilar Rollari</div>
          <div style="max-width:200px;margin:0 auto;">
            <canvas id="rolesDonut" height="200"></canvas>
          </div>
          <div style="margin-top:14px;">
            <?php
            $role_clrs = ['admin'=>'#8b5cf6','teacher'=>'#3b82f6','student'=>'#10b981','parent'=>'#f59e0b'];
            $role_emojis = ['admin'=>'👑','teacher'=>'👨‍🏫','student'=>'🎓','parent'=>'👪'];
            $total_users = array_sum(array_column($roles, 'cnt'));
            foreach($roles as $r):
            ?>
            <div class="legend-row">
              <span class="legend-dot" style="background:<?= $role_clrs[$r['role']] ?? '#8b5cf6' ?>"></span>
              <?= $role_emojis[$r['role']] ?? '' ?> <?= $role_labels[$r['role']] ?? $r['role'] ?>
              &nbsp;<b style="color:#e6edf3"><?= $r['cnt'] ?> ta</b>
              <span style="margin-left:auto;color:<?= $role_clrs[$r['role']] ?? '#8b5cf6' ?>">
                <?= $total_users > 0 ? round($r['cnt']/$total_users*100) : 0 ?>%
              </span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div><!-- /page-content -->
  </div>
</div>

<script>
Chart.defaults.color = '#8b949e';
Chart.defaults.borderColor = 'rgba(240,246,252,0.06)';
Chart.defaults.font.family = "'Inter', sans-serif";

// 1. 12 oylik tushum
new Chart(document.getElementById('incomeChart12'), {
  type: 'line',
  data: {
    labels: <?= json_encode($monthly_labels) ?>,
    datasets: [{
      label: "Tushum",
      data: <?= json_encode($monthly_income) ?>,
      borderColor: '#8b5cf6',
      backgroundColor: 'rgba(139,92,246,0.10)',
      borderWidth: 2.5,
      pointBackgroundColor: '#8b5cf6',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 5,
      tension: 0.45,
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
        callbacks: { label: c => ' ' + c.parsed.y.toLocaleString() + " so'm" }
      }
    },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.03)' } },
      y: {
        grid: { color: 'rgba(255,255,255,0.03)' },
        ticks: { callback: v => (v>=1000000 ? (v/1000000).toFixed(1)+'M' : (v/1000).toFixed(0)+'K') },
        beginAtZero: true
      }
    }
  }
});

// 2. Haftalik bar
new Chart(document.getElementById('weeklyChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($weekly_days) ?>,
    datasets: [{
      label: "Kunlik tushum",
      data: <?= json_encode($weekly_pay) ?>,
      backgroundColor: 'rgba(59,130,246,0.6)',
      borderColor: '#3b82f6',
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1c2333',
        borderColor: 'rgba(59,130,246,0.4)',
        borderWidth: 1,
        callbacks: { label: c => ' ' + c.parsed.y.toLocaleString() + " so'm" }
      }
    },
    scales: {
      x: { grid: { display: false } },
      y: {
        grid: { color: 'rgba(255,255,255,0.03)' },
        ticks: { callback: v => (v/1000).toFixed(0)+'K' },
        beginAtZero: true
      }
    }
  }
});

// 3. To'lov holatlari donut
new Chart(document.getElementById('payDonut'), {
  type: 'doughnut',
  data: {
    labels: ["To'langan","Kutilmoqda","Muddati o'tgan"],
    datasets: [{
      data: [<?= $pay_paid ?>, <?= $pay_pending ?>, <?= $pay_overdue ?>],
      backgroundColor: ['rgba(16,185,129,0.85)','rgba(245,158,11,0.85)','rgba(239,68,68,0.85)'],
      borderColor: ['#10b981','#f59e0b','#ef4444'],
      borderWidth: 2, hoverOffset: 10,
    }]
  },
  options: {
    cutout: '70%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1c2333', borderColor: 'rgba(99,102,241,0.4)', borderWidth: 1,
        callbacks: { label: c => ' ' + c.parsed.toLocaleString() + " so'm" }
      }
    }
  }
});

// 4. Guruhlar bar
<?php if(!empty($groups_data)): ?>
new Chart(document.getElementById('groupsChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($groups_data, 'name')) ?>,
    datasets: [{
      label: "O'quvchilar",
      data: <?= json_encode(array_map('intval', array_column($groups_data, 'cnt'))) ?>,
      backgroundColor: ['#8b5cf699','#3b82f699','#10b98199','#f59e0b99','#ef444499','#06b6d499','#ec489999','#84cc1699'],
      borderColor:     ['#8b5cf6',  '#3b82f6',  '#10b981',  '#f59e0b',  '#ef4444',  '#06b6d4',  '#ec4899',  '#84cc16'],
      borderWidth: 2, borderRadius: 7,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1c2333', borderColor: 'rgba(139,92,246,0.4)', borderWidth: 1,
        callbacks: { label: c => ' ' + c.parsed.y + " ta o'quvchi" }
      }
    },
    scales: {
      x: { grid: { display: false } },
      y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { stepSize: 1 }, beginAtZero: true }
    }
  }
});
<?php endif; ?>

// 5. Rollar donut
new Chart(document.getElementById('rolesDonut'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_map(fn($r) => $role_labels[$r['role']] ?? $r['role'], $roles)) ?>,
    datasets: [{
      data: <?= json_encode(array_map(fn($r) => (int)$r['cnt'], $roles)) ?>,
      backgroundColor: <?= json_encode(array_map(fn($r) => ($role_clrs[$r['role']] ?? '#8b5cf6').'cc', $roles)) ?>,
      borderColor:     <?= json_encode(array_map(fn($r) => $role_clrs[$r['role']] ?? '#8b5cf6', $roles)) ?>,
      borderWidth: 2, hoverOffset: 8,
    }]
  },
  options: {
    cutout: '68%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1c2333', borderColor: 'rgba(99,102,241,0.4)', borderWidth: 1,
        callbacks: { label: c => ' ' + c.parsed + ' ta' }
      }
    }
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
