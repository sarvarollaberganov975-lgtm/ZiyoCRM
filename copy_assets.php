<?php
$targetDir = 'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM/assets';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

copy(
    'C:/Users/user/.gemini/antigravity/brain/8e014e3b-ee6c-444d-8d3a-c7c86f7fdf2c/.user_uploaded/media__1785218999164.png',
    $targetDir . '/logo.png'
);

copy(
    'C:/Users/user/.gemini/antigravity/brain/8e014e3b-ee6c-444d-8d3a-c7c86f7fdf2c/.user_uploaded/media__1785243158139.png',
    $targetDir . '/brand_header.png'
);

echo "Assets created successfully.\n";
