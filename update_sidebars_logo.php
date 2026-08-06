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
        $c = str_replace('../assets/logo.png', '../assets/logo_clean.png', $c);
        $c = str_replace('height: 36px; width: 36px;', 'height: 42px; width: 42px;', $c);
        file_put_contents($sidebar, $c);
        echo "Updated sidebar: " . $sidebar . "\n";
    }
}
