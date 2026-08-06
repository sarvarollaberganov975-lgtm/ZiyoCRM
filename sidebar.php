<?php $current = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">
      <img src="../assets/ziyo_clean_icon.png" alt="ZiyoCRM Logo" style="height: 45px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,162,255,0.3));">
      <div>
        <h2 style="background:linear-gradient(135deg,#fcd34d,#fbbf24);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-size:14px;font-weight:800;">ZiyoCRM</h2>
        <p>Ota-ona Panel</p>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-title">Asosiy</div>
    <a href="dashboard.php" class="nav-item <?= $current==='dashboard.php'?'active':'' ?>" style="<?= $current==='dashboard.php'?'background:rgba(217,119,6,0.2);color:#fcd34d;':'' ?>">
      <span class="nav-icon">📊</span> Bosh sahifa
    </a>

    <div class="nav-section-title">Farzandim</div>
    <a href="children.php" class="nav-item <?= $current==='children.php'?'active':'' ?>" style="<?= $current==='children.php'?'background:rgba(217,119,6,0.2);color:#fcd34d;':'' ?>">
      <span class="nav-icon">👶</span> Farzandlar
    </a>
    <a href="profile.php" class="nav-item <?= $current==='profile.php'?'active':'' ?>" style="<?= $current==='profile.php'?'background:rgba(217,119,6,0.2);color:#fcd34d;':'' ?>">
      <span class="nav-icon">👤</span> Profil & Telegram
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar" style="background:linear-gradient(135deg,#d97706,#b45309);">
        <?= strtoupper(substr($_SESSION['user_name']??'P',0,1)) ?>
      </div>
      <div class="user-info-text">
        <div class="uname"><?= htmlspecialchars($_SESSION['user_name']??'') ?></div>
        <div class="urole">👪 Ota-ona</div>
      </div>
    </div>
    <button onclick="toggleTheme()" class="theme-toggle-btn" style="width:100%; justify-content:center; margin-bottom:10px;">☀️ Kun rejimi</button>
    <a href="../logout.php" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;">🚪 Chiqish</a>
  </div>
</div>
