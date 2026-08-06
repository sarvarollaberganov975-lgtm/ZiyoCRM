<?php
// Update index.php header logo
$indexPath = 'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/index.php';
$indexContent = file_get_contents($indexPath);

// Replace brand-logo-wrap in index.php with brand_header.png logo
$oldLogoWrap = '<div class="brand-logo-wrap">
      <div class="brand-icon">🏫</div>
      <div class="brand-text">
        <h1>ZiyoCRM</h1>
        <span>Ta\'lim markazi boshqaruv tizimi</span>
      </div>
    </div>';

$newLogoWrap = '<div class="brand-logo-wrap" style="display: inline-flex; align-items: center; justify-content: center;">
      <img src="assets/brand_header.png" alt="ZiyoCRM" style="height: 64px; width: auto; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(99,102,241,0.3));">
    </div>';

if (strpos($indexContent, '<div class="brand-icon">🏫</div>') !== false) {
    $indexContent = str_replace($oldLogoWrap, $newLogoWrap, $indexContent);
    file_put_contents($indexPath, $indexContent);
    echo "index.php logo updated successfully.\n";
}

// Update sidebars in all 4 roles
$sidebars = [
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/admin/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/teacher/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/student/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/parent/sidebar.php',
];

foreach ($sidebars as $sidebar) {
    if (file_exists($sidebar)) {
        $c = file_get_contents($sidebar);
        // Replace logo icon emoji block with logo image tag
        $c = preg_replace(
            '/<div class="logo-icon"[^>]*>.*?<\/div>/s',
            '<img src="../assets/logo.png" alt="ZiyoCRM Logo" style="height: 36px; width: 36px; object-fit: contain; border-radius: 8px;">',
            $c
        );
        file_put_contents($sidebar, $c);
        echo "Sidebar updated: " . $sidebar . "\n";
    }
}
