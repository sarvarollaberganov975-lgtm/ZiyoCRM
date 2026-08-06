<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? 'admin';
$user_name = $_SESSION['user_name'] ?? 'Foydalanuvchi';

$role_titles = [
    'admin'   => ['title' => 'Admin Panel', 'badge' => '👑 Admin', 'color' => '#8b5cf6'],
    'teacher' => ['title' => "O'qituvchi Panel", 'badge' => "👨‍🏫 O'qituvchi", 'color' => '#3b82f6'],
    'student' => ['title' => "O'quvchi Panel", 'badge' => "🎓 O'quvchi", 'color' => '#10b981'],
    'parent'  => ['title' => 'Ota-ona Panel', 'badge' => '👪 Ota-ona', 'color' => '#f59e0b'],
];

$role_meta = $role_titles[$role] ?? $role_titles['admin'];
?>
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">
      <img src="../assets/images/ziyo_clean_icon.png" alt="ZiyoCRM Logo" style="height: 42px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,162,255,0.3));" onerror="this.src='../assets/ziyo_clean_icon.png'">
      <div>
        <h2 style="background:linear-gradient(135deg,#fcd34d,#fbbf24);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-size:15px;font-weight:800;margin:0;">ZiyoCRM</h2>
        <p style="font-size:11px;color:<?= $role_meta['color'] ?>;margin:2px 0 0 0;font-weight:600;"><?= $role_meta['title'] ?></p>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-title">Asosiy</div>
    <a href="dashboard.php" class="nav-item <?= $current_page==='dashboard.php'?'active':'' ?>">
      <span class="nav-icon">📊</span> Bosh sahifa
    </a>

    <?php if ($role === 'admin'): ?>
    <a href="analytics.php" class="nav-item <?= $current_page==='analytics.php'?'active':'' ?>">
      <span class="nav-icon">📈</span> Analitika
    </a>
    <a href="target_crm.php" class="nav-item <?= $current_page==='target_crm.php'?'active':'' ?>">
      <span class="nav-icon">🎯</span> Target CRM (Lidlar)
    </a>

    <div class="nav-section-title">Boshqaruv</div>
    <a href="users.php" class="nav-item <?= $current_page==='users.php'?'active':'' ?>">
      <span class="nav-icon">👥</span> Foydalanuvchilar
    </a>
    <a href="groups.php" class="nav-item <?= $current_page==='groups.php'?'active':'' ?>">
      <span class="nav-icon">📚</span> Guruhlar
    </a>
    <a href="attendance.php" class="nav-item <?= $current_page==='attendance.php'?'active':'' ?>">
      <span class="nav-icon">📋</span> Davomat
    </a>
    <a href="warnings.php" class="nav-item <?= $current_page==='warnings.php'?'active':'' ?>">
      <span class="nav-icon">⚠️</span> Tanbehlar
    </a>
    <a href="payments.php" class="nav-item <?= $current_page==='payments.php'?'active':'' ?>">
      <span class="nav-icon">💳</span> To'lovlar
    </a>

    <div class="nav-section-title">O'quv & Kontent</div>
    <a href="announcements.php" class="nav-item <?= $current_page==='announcements.php'?'active':'' ?>">
      <span class="nav-icon">📢</span> E'lonlar
    </a>
    <a href="manage_shop.php" class="nav-item <?= $current_page==='manage_shop.php'?'active':'' ?>">
      <span class="nav-icon">🛍️</span> Do'kon Boshqaruvi
    </a>
    <a href="manage_tests_ai.php" class="nav-item <?= $current_page==='manage_tests_ai.php'?'active':'' ?>">
      <span class="nav-icon">🤖</span> AI Test Generator
    </a>

    <div class="nav-section-title">Sozlamalar</div>
    <a href="telegram.php" class="nav-item <?= $current_page==='telegram.php'?'active':'' ?>">
      <span class="nav-icon">📱</span> Telegram Bot
    </a>
    <a href="send_message.php" class="nav-item <?= $current_page==='send_message.php'?'active':'' ?>">
      <span class="nav-icon">💬</span> Xabar Yuborish
    </a>

    <?php elseif ($role === 'teacher'): ?>
    <div class="nav-section-title">Darslar</div>
    <a href="attendance.php" class="nav-item <?= $current_page==='attendance.php'?'active':'' ?>">
      <span class="nav-icon">📋</span> Davomat
    </a>
    <a href="groups.php" class="nav-item <?= $current_page==='groups.php'?'active':'' ?>">
      <span class="nav-icon">📚</span> Guruhlarim
    </a>
    <a href="manage_tests.php" class="nav-item <?= $current_page==='manage_tests.php'?'active':'' ?>">
      <span class="nav-icon">📝</span> Testlar Boshqaruvi
    </a>

    <?php elseif ($role === 'student'): ?>
    <div class="nav-section-title">O'qish</div>
    <a href="tests.php" class="nav-item <?= $current_page==='tests.php'?'active':'' ?>">
      <span class="nav-icon">📝</span> Test Yechish
    </a>
    <a href="homework.php" class="nav-item <?= $current_page==='homework.php'?'active':'' ?>">
      <span class="nav-icon">📖</span> Uy Vazifalari
    </a>
    <a href="shop.php" class="nav-item <?= $current_page==='shop.php'?'active':'' ?>">
      <span class="nav-icon">🛒</span> Do'kon (Coins)
    </a>
    <a href="payments.php" class="nav-item <?= $current_page==='payments.php'?'active':'' ?>">
      <span class="nav-icon">💳</span> To'lovlarim
    </a>

    <?php elseif ($role === 'parent'): ?>
    <div class="nav-section-title">Farzandim</div>
    <a href="children.php" class="nav-item <?= $current_page==='children.php'?'active':'' ?>">
      <span class="nav-icon">👶</span> Farzandlar
    </a>
    <a href="profile.php" class="nav-item <?= $current_page==='profile.php'?'active':'' ?>">
      <span class="nav-icon">👤</span> Profil & Telegram
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar" style="background:<?= $role_meta['color'] ?>;">
        <?= strtoupper(substr($user_name, 0, 1)) ?>
      </div>
      <div class="user-info-text">
        <div class="uname"><?= htmlspecialchars($user_name) ?></div>
        <div class="urole"><?= $role_meta['badge'] ?></div>
      </div>
    </div>
    <button onclick="toggleTheme()" class="theme-toggle-btn" style="width:100%; justify-content:center; margin-bottom:10px;">☀️ Kun rejimi</button>
    <a href="../logout.php" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;">🚪 Chiqish</a>
  </div>
</div>
