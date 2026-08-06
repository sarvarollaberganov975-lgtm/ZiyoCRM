Add-Type -AssemblyName System.Drawing

$srcPath = "C:\Users\user\.gemini\antigravity\brain\8e014e3b-ee6c-444d-8d3a-c7c86f7fdf2c\.user_uploaded\media__1785218999164.png"
$assetsDir = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets"

# 1. Save original main logo as main_logo.png
copy-item $srcPath "$assetsDir\main_logo.png" -Force

# 2. Convert to crisp ICO (256x256, 128x128, 64x64, 32x32, 16x16 formats)
$img = [System.Drawing.Image]::FromFile($srcPath)
$bmp = New-Object System.Drawing.Bitmap(256, 256)
$g = [System.Drawing.Graphics]::FromImage($bmp)

$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
$g.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality

# Clear canvas with pure solid background matching logo or pure white for Windows shortcut consistency
$g.Clear([System.Drawing.Color]::White)

# Fit perfectly
$scale = [Math]::Min(240 / $img.Width, 240 / $img.Height)
$w = [int]($img.Width * $scale)
$h = [int]($img.Height * $scale)
$x = [int]((256 - $w) / 2)
$y = [int]((256 - $h) / 2)

$g.DrawImage($img, $x, $y, $w, $h)

$g.Dispose()
$img.Dispose()

$icoFile = "$assetsDir\ziyo_crm_crisp.ico"
$icon = [System.Drawing.Icon]::FromHandle($bmp.GetHicon())
$stream = [System.IO.File]::OpenWrite($icoFile)
$icon.Save($stream)
$stream.Close()
$icon.Dispose()
$bmp.Dispose()

Write-Host "Crisp ICO created successfully!"

# Update Desktop shortcut with crisp icon
$WshShell = New-Object -ComObject WScript.Shell
$lnk = $WshShell.CreateShortcut("C:\Users\user\Desktop\Ziyo_CRM_Start.lnk")
$lnk.TargetPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\Start.bat"
$lnk.WorkingDirectory = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM"
$lnk.IconLocation = $icoFile + ",0"
$lnk.Description = "ZiyoCRM - Ta'lim markazi boshqaruv tizimi"
$lnk.Save()

Write-Host "Shortcut updated with crisp logo icon!"
