<?php
$shortcut = 'C:/Users/user/Desktop/Ziyo_CRM_Start.lnk';
if (file_exists($shortcut)) {
    $content = file_get_contents($shortcut);
    $newContent = str_replace('Doktor_School', 'Ziyo_CRM', $content);
    $newContent = str_replace('ZiyoCRM', 'ZiyoCRM', $newContent);
    file_put_contents($shortcut, $newContent);
    echo "Shortcut link updated successfully.\n";
} else {
    echo "Shortcut not found.\n";
}
