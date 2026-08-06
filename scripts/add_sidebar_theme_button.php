<?php
$sidebars = [
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/admin/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/teacher/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/student/sidebar.php',
    'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/parent/sidebar.php',
];

$themeButtonHtml = '<button onclick="toggleTheme()" class="theme-toggle-btn" style="width:100%; justify-content:center; margin-bottom:10px;">☀️ Kun rejimi</button>';

foreach ($sidebars as $sidebar) {
    if (file_exists($sidebar)) {
        $c = file_get_contents($sidebar);
        if (strpos($c, 'toggleTheme()') === false) {
            $c = str_replace('<a href="../logout.php"', $themeButtonHtml . "\n    " . '<a href="../logout.php"', $c);
            file_put_contents($sidebar, $c);
            echo "Theme toggle button added to: " . $sidebar . "\n";
        }
    }
}
