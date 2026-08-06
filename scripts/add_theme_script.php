<?php
$projectDir = 'C:/Users/user/.gemini/antigravity/scratch/ZiyoCRM';

$jsThemeScript = <<<HTML
<script>
// === KUN / TUN REJIMI (THEME TOGGLE) ===
(function() {
  const savedTheme = localStorage.getItem('ziyo_theme') || 'dark';
  if (savedTheme === 'light') {
    document.documentElement.setAttribute('data-theme', 'light');
  }
})();

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const newTheme = current === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('ziyo_theme', newTheme);
  updateThemeButtons(newTheme);
}

function updateThemeButtons(theme) {
  document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
    btn.innerHTML = theme === 'light' ? '🌙 Tun rejimi' : '☀️ Kun rejimi';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const current = localStorage.getItem('ziyo_theme') || 'dark';
  updateThemeButtons(current);
});
</script>
HTML;

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectDir));

foreach ($files as $file) {
    if ($file->isFile() && pathinfo($file->getPathname(), PATHINFO_EXTENSION) === 'php') {
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        
        // Add theme script before </body> if not present
        if (strpos($content, '</body>') !== false && strpos($content, 'toggleTheme') === false) {
            $content = str_replace('</body>', $jsThemeScript . "\n</body>", $content);
            file_put_contents($filePath, $content);
            echo "Theme script added to: " . $filePath . "\n";
        }
    }
}
