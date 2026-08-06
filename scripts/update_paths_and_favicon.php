<?php
$projectDir = 'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM';

// 1. Favicon link html tag to add
$faviconTag = '<link rel="icon" type="image/png" href="assets/logo.png">' . "\n" . '<link rel="shortcut icon" href="assets/ziyo_crm.ico">';
$faviconTagSub = '<link rel="icon" type="image/png" href="../assets/logo.png">' . "\n" . '<link rel="shortcut icon" href="../assets/ziyo_crm.ico">';

// 2. Scan all .php files and update path references and add favicon
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectDir));

foreach ($files as $file) {
    if ($file->isFile()) {
        $filePath = $file->getPathname();
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (in_array($ext, ['php', 'bat', 'js', 'css'])) {
            $content = file_get_contents($filePath);
            
            // Replace ZiyoCRM paths with ZiyoCRM
            $newContent = str_replace(
                ['ZiyoCRM', 'ziyo_crm', 'ziyo_crm_bot', 'ziyo_crm.db'],
                ['ZiyoCRM', 'ziyo_crm', 'ziyo_crm_bot', 'ziyo_crm.db'],
                $content
            );
            
            // Add Favicon if it's PHP file and has <head>
            if ($ext === 'php' && strpos($newContent, '<head>') !== false && strpos($newContent, 'rel="icon"') === false) {
                $isSub = (dirname($filePath) !== $projectDir);
                $tag = $isSub ? $faviconTagSub : $faviconTag;
                $newContent = str_replace('<head>', "<head>\n" . $tag, $newContent);
            }
            
            if ($newContent !== $content) {
                file_put_contents($filePath, $newContent);
                echo "Updated: " . $filePath . "\n";
            }
        }
    }
}
