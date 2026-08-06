Add-Type -AssemblyName System.Drawing

# Clean cropped logo location
$pngPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\logo_clean.png"
$icoPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\ziyo_crm_clean.ico"

$img = [System.Drawing.Image]::FromFile($pngPath)

# Create 256x256 bitmap with transparent background
$bmp = New-Object System.Drawing.Bitmap(256, 256)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic

# Draw transparent background
$g.Clear([System.Drawing.Color]::Transparent)

# Fit image nicely inside 256x256 without distortion
$scale = [Math]::Min(256 / $img.Width, 256 / $img.Height)
$newW = [int]($img.Width * $scale)
$newH = [int]($img.Height * $scale)
$posX = [int]((256 - $newW) / 2)
$posY = [int]((256 - $newH) / 2)

$g.DrawImage($img, $posX, $posY, $newW, $newH)
$g.Dispose()
$img.Dispose()

# Save ICO
$icon = [System.Drawing.Icon]::FromHandle($bmp.GetHicon())
$stream = [System.IO.File]::OpenWrite($icoPath)
$icon.Save($stream)
$stream.Close()
$icon.Dispose()
$bmp.Dispose()

Write-Host "Transparent ICO created: $icoPath"

# Update Desktop shortcut with new clean ICO
$WshShell = New-Object -ComObject WScript.Shell
$lnk = $WshShell.CreateShortcut("C:\Users\user\Desktop\Ziyo_CRM_Start.lnk")
$lnk.TargetPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\Start.bat"
$lnk.WorkingDirectory = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM"
$lnk.IconLocation = $icoPath + ",0"
$lnk.Description = "ZiyoCRM - Ta'lim markazi boshqaruv tizimi"
$lnk.Save()
Write-Host "Desktop shortcut icon updated with clean icon!"
