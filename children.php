<?php
require_once '../includes/config.php';
requireLogin('parent');
$db   = getDB();
$user = getCurrentUser();
$pid  = $user['id'];

$children = $db->prepare("
    SELECT u.* FROM parent_student ps JOIN users u ON ps.student_id=u.id WHERE ps.parent_id=?
");
$children->execute([$pid]);
$children = $children->fetchAll(PDO::FETCH_ASSOC);
?><!DOCTYPE html>
<html lang="uz">
<head>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="shortcut icon" href="../assets/ziyo_crm.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Farzandlarim — ZiyoCRM</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">👶 Farzandlarim</div>
      <div class="topbar-right">
        <a href="../logout.php" class="btn btn-outline btn-sm">🚪 Chiqish</a>
      </div>
    </div>
    <div class="page-content">
      <?php if(empty($children)): ?>
      <div class="empty-state fade-in">
        <div class="es-icon">👶</div>
        <h4>Farzand qo'shilmagan</h4>
        <p>Admin sizning farzandingizni tizimga qo'shishi kerak</p>
      </div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;" class="fade-in">
        <?php foreach($children as $child):
          $wq = $db->prepare("SELECT * FROM warnings WHERE student_id=? ORDER BY created_at DESC");
          $wq->execute([$child['id']]);
          $warnings = $wq->fetchAll(PDO::FETCH_ASSOC);

          $aq = $db->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=? AND status='absent'");
          $aq->execute([$child['id']]);
          $absences = $aq->fetchColumn();
        ?>
        <div style="background:#161b22;border:1px solid rgba(240,246,252,0.08);border-radius:18px;overflow:hidden;">
          <!-- Header -->
          <div style="background:linear-gradient(135deg,rgba(217,119,6,0.2),rgba(180,83,9,0.1));padding:24px;border-bottom:1px solid rgba(240,246,252,0.06);">
            <div style="display:flex;align-items:center;gap:14px;">
              <div style="width:56px;height:56px;background:linear-gradient(135deg,#d97706,#b45309);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;">
                <?= strtoupper(substr($child['full_name'],0,1)) ?>
              </div>
              <div>
                <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($child['full_name']) ?></div>
                <div style="font-size:12px;color:#8b949e;">@<?= htmlspecialchars($child['username']) ?></div>
                <div style="margin-top:4px;">
                  <span class="badge <?= $child['is_active']?'badge-success':'badge-danger' ?>">
                    <?= $child['is_active']?'✅ Faol':'❌ Nofaol' ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <!-- Stats -->
          <div style="padding:16px;display:grid;grid-template-columns:1fr 1fr;gap:10px;border-bottom:1px solid rgba(240,246,252,0.06);">
            <div style="text-align:center;padding:12px;background:rgba(239,68,68,0.07);border-radius:10px;">
              <div style="font-size:22px;font-weight:800;color:#fca5a5;"><?= count($warnings) ?></div>
              <div style="font-size:11px;color:#8b949e;">Tanbehllar</div>
            </div>
            <div style="text-align:center;padding:12px;background:rgba(245,158,11,0.07);border-radius:10px;">
              <div style="font-size:22px;font-weight:800;color:#fcd34d;"><?= $absences ?></div>
              <div style="font-size:11px;color:#8b949e;">Devomatsizlik</div>
            </div>
          </div>
          <!-- Warnings list -->
          <div style="padding:16px;">
            <div style="font-size:11px;font-weight:700;color:#8b949e;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;">So'nggi Tanbehllar</div>
            <?php if(empty($warnings)): ?>
            <div style="text-align:center;padding:16px;color:#8b949e;font-size:13px;">🎉 Tanbeh yo'q</div>
            <?php else: ?>
            <?php foreach(array_slice($warnings,0,3) as $w): ?>
            <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.15);border-radius:10px;padding:12px;margin-bottom:8px;">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <span class="badge badge-danger"><?= htmlspecialchars($w['type']) ?></span>
                <span style="font-size:11px;color:#8b949e;"><?= date('d.m.Y', strtotime($w['created_at'])) ?></span>
              </div>
              <div style="font-size:12px;margin-top:6px;color:#e6edf3;"><?= htmlspecialchars($w['description']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
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
