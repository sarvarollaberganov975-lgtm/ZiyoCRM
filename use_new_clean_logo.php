<?php
$sidebars = [
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/admin/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/teacher/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/student/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/parent/sidebar.php',
];

foreach ($sidebars as $sidebar) {
    if (file_exists($sidebar)) {
        $c = file_get_contents($sidebar);
        $c = str_replace('../assets/main_logo.png', '../assets/ziyo_clean_icon.png', $c);
        $c = str_replace('../assets/logo_clean.png', '../assets/ziyo_clean_icon.png', $c);
        $c = str_replace('../assets/logo.png', '../assets/ziyo_clean_icon.png', $c);
        $c = str_replace('style="height: 42px; width: auto; object-fit: contain; background: #fff; padding: 3px; border-radius: 8px;"', 'style="height: 45px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,162,255,0.3));"', $c);
        file_put_contents($sidebar, $c);
        echo "Updated sidebar with clean new logo: " . $sidebar . "\n";
    }
}
