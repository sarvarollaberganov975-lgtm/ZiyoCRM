Add-Type -AssemblyName System.Drawing

$pngPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\logo_clean.png"
$icoPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\ziyo_crm_app.ico"

$src = [System.Drawing.Bitmap]::FromFile($pngPath)

# High resolution transparent icon canvas (256x256)
$bmp = New-Object System.Drawing.Bitmap(256, 256)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
$g.Clear([System.Drawing.Color]::Transparent)

# Fit image nicely without white border noise
$scale = [Math]::Min(230 / $src.Width, 230 / $src.Height)
$newW = [int]($src.Width * $scale)
$newH = [int]($src.Height * $scale)
$posX = [int]((256 - $newW) / 2)
$posY = [int]((256 - $newH) / 2)

$g.DrawImage($src, $posX, $posY, $newW, $newH)
$g.Dispose()
$src.Dispose()

# Save ICO
$icon = [System.Drawing.Icon]::FromHandle($bmp.GetHicon())
$stream = [System.IO.File]::OpenWrite($icoPath)
$icon.Save($stream)
$stream.Close()
$icon.Dispose()
$bmp.Dispose()

Write-Host "HD transparent ICO generated: $icoPath"

# Recreate shortcut with clear path and clean icon
$WshShell = New-Object -ComObject WScript.Shell
$lnk = $WshShell.CreateShortcut("C:\Users\user\Desktop\Ziyo_CRM_Start.lnk")
$lnk.TargetPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\Start.bat"
$lnk.WorkingDirectory = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM"
$lnk.IconLocation = $icoPath + ",0"
$lnk.Description = "ZiyoCRM - Ta'lim markazi boshqaruv tizimi"
$lnk.Save()

Write-Host "Desktop shortcut fixed and refreshed!"
