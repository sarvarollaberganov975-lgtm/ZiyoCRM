<?php
function processDir($dir) {
    $items = glob($dir . '/{*,.*}', GLOB_BRACE);
    foreach ($items as $item) {
        $basename = basename($item);
        if ($basename === '.' || $basename === '..') continue;
        if (is_dir($item)) {
            processDir($item);
        } else {
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            if (in_array($ext, ['php', 'bat', 'html', 'js', 'css', 'md'])) {
                $content = file_get_contents($item);
                if (stripos($content, 'doktor') !== false) {
                    $newContent = str_replace(
                        ['ZiyoCRM', 'ZiyoCRM', 'ziyo_crm_bot', 'ziyo_crm.db', 'ziyo_crm', 'ZiyoCRM'],
                        ['ZiyoCRM', 'ZiyoCRM', 'ziyo_crm_bot', 'ziyo_crm.db', 'ziyo_crm', 'ZiyoCRM'],
                        $content
                    );
                    file_put_contents($item, $newContent);
                    echo "Updated: " . $item . "\n";
                }
            }
        }
    }
}
processDir('C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM');
