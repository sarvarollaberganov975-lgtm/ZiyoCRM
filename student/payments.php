<?php
require_once '../includes/config.php';
requireLogin('student');
$db = getDB();
$user = getCurrentUser();
$sid = $user['id'];

$payments = $db->prepare("SELECT * FROM payments WHERE student_id=? ORDER BY created_at DESC");
$payments->execute([$sid]);
$payments = $payments->fetchAll(PDO::FETCH_ASSOC);

$total_paid = array_sum(array_column(array_filter($payments, fn($p)=>$p['status']==='paid'), 'amount'));
$total_pending = array_sum(array_column(array_filter($payments, fn($p)=>$p['status']==='pending'), 'amount'));
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>To'lovlarim — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">💰 To'lovlarim</div>
      <div class="topbar-right">
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>

    <div class="page-content">
      <div class="stats-grid">
        <div class="stat-card fade-in" style="--card-color:#059669">
          <div class="stat-icon">✅</div>
          <div class="stat-value" style="font-size:18px"><?= number_format($total_paid, 0, '.', ' ') ?></div>
          <div class="stat-label">To'langan (so'm)</div>
        </div>
        <div class="stat-card fade-in" style="--card-color:#d97706">
          <div class="stat-icon">⏳</div>
          <div class="stat-value" style="font-size:18px"><?= number_format($total_pending, 0, '.', ' ') ?></div>
          <div class="stat-label">Kutilmoqda (so'm)</div>
        </div>
      </div>

      <div class="data-table-wrapper fade-in">
        <div class="table-header" style="display:flex; justify-content:space-between; align-items:center;">
          <h3>💰 To'lovlar Tarixi</h3>
          <button class="btn btn-primary btn-sm" onclick="openPayAppsModal()">📲 Onlayn To'lov Qilish (Click / Payme...)</button>
        </div>
        <?php if (empty($payments)): ?>
        <div class="empty-state"><div class="empty-icon">💰</div><p>To'lov ma'lumoti yo'q</p></div>
        <?php else: ?>
        <table>
          <thead><tr><th>Oy</th><th>Miqdor</th><th>Holat</th><th>Izoh</th><th>Sana</th><th>To'lov</th></tr></thead>
          <tbody>
            <?php foreach ($payments as $p):
              $bs = ['paid'=>'badge-success','pending'=>'badge-warning','overdue'=>'badge-danger'];
              $ls = ['paid'=>"✅ To'langan",'pending'=>'⏳ Kutilmoqda','overdue'=>"❌ Muddati o'tgan"];
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($p['month']) ?></strong></td>
              <td><?= number_format($p['amount'], 0, '.', ' ') ?> so'm</td>
              <td><span class="badge <?= $bs[$p['status']] ?? 'badge-primary' ?>"><?= $ls[$p['status']] ?? $p['status'] ?></span></td>
              <td><?= htmlspecialchars($p['note'] ?: '—') ?></td>
              <td><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
              <td>
                <?php if ($p['status'] !== 'paid'): ?>
                  <button class="btn btn-sm btn-success" onclick="payForMonth('<?= htmlspecialchars($p['month']) ?>', <?= $p['amount'] ?>)">📲 To'lash</button>
                <?php else: ?>
                  <span style="color:#34d399; font-size:12px">✅ Bajarildi</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

<!-- Onlayn To'lov Ilovalari Modali -->
<div class="modal-overlay" id="payAppsModal">
  <div class="modal" style="max-width:440px; text-align:center;">
    <div class="modal-header">
      <h3>📲 To'lov Ilovasini Tanlang</h3>
      <button class="modal-close" onclick="closeModal('payAppsModal')">✕</button>
    </div>
    <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;" id="pay_app_desc">
      To'lov ilovasini tanlang, ilova avtomatik ochiladi va pul hisoblashda xatolik bo'lmaydi:
    </p>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:16px;">
      <!-- Click -->
      <a id="btn_click" href="https://my.click.uz/" target="_blank" class="btn" style="background:#00aaff; color:#fff; font-weight:700; display:flex; flex-direction:column; align-items:center; padding:14px; border-radius:12px; text-decoration:none;">
        <span style="font-size:20px;">🔹</span> CLICK
      </a>
      <!-- Payme -->
      <a id="btn_payme" href="https://payme.uz/" target="_blank" class="btn" style="background:#00cccc; color:#fff; font-weight:700; display:flex; flex-direction:column; align-items:center; padding:14px; border-radius:12px; text-decoration:none;">
        <span style="font-size:20px;">🟢</span> PAYME
      </a>
      <!-- Paynet -->
      <a id="btn_paynet" href="https://paynet.uz/" target="_blank" class="btn" style="background:#ff6600; color:#fff; font-weight:700; display:flex; flex-direction:column; align-items:center; padding:14px; border-radius:12px; text-decoration:none;">
        <span style="font-size:20px;">🔴</span> PAYNET
      </a>
      <!-- Xazna -->
      <a id="btn_xazna" href="https://xazna.uz/" target="_blank" class="btn" style="background:#6b21a8; color:#fff; font-weight:700; display:flex; flex-direction:column; align-items:center; padding:14px; border-radius:12px; text-decoration:none;">
        <span style="font-size:20px;">🟣</span> XAZNA
      </a>
    </div>

    <button type="button" class="btn btn-outline" onclick="closeModal('payAppsModal')" style="width:100%">Bekor qilish</button>
  </div>
</div>

<script>
function openPayAppsModal() {
  document.getElementById('pay_app_desc').textContent = "Ilovaga o'tib to'lovni tasdiqlang. Karta raqam yozib o'tirish shart emas!";
  openModal('payAppsModal');
}

function payForMonth(month, amount) {
  document.getElementById('pay_app_desc').innerHTML = "<b>" + month + "</b> oyi uchun to'lov summasi: <b style='color:#34d399'>" + amount.toLocaleString() + " so'm</b>.<br>To'lov ilovasini tanlang:";
  openModal('payAppsModal');
}
</script>
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
