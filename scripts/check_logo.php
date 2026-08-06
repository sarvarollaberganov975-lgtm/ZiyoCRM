<?php
$path = 'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/assets/logo.png';
$info = getimagesize($path);
echo "Width: " . $info[0] . ", Height: " . $info[1] . "\n";

