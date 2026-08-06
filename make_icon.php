<?php
$pngPath = 'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/assets/logo.png';
$icoPath = 'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/assets/app.ico';

list($w, $h) = getimagesize($pngPath);
$src = imagecreatefrompng($pngPath);
$dst = imagecreatetruecolor(64, 64);

imagealphablending($dst, false);
imagesavealpha($dst, true);
imagecopyresampled($dst, $src, 0, 0, 0, 0, 64, 64, $w, $h);

// Save as PNG named app.ico or app_icon.png
imagepng($dst, 'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/assets/app_icon.png');
echo "Icon generated.\n";
