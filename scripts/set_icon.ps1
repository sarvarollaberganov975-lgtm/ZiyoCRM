Add-Type -AssemblyName System.Drawing

$pngPath = "C:\Users\user\.gemini\antigravity\scratch\DOKTOR_SCHOOL\assets\logo.png"
$icoPath = "C:\Users\user\.gemini\antigravity\scratch\DOKTOR_SCHOOL\assets\ziyo_crm.ico"

$img = [System.Drawing.Image]::FromFile($pngPath)
$bmp = New-Object System.Drawing.Bitmap(256, 256)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g.DrawImage($img, 0, 0, 256, 256)
$g.Dispose()
$img.Dispose()

$icon = [System.Drawing.Icon]::FromHandle($bmp.GetHicon())
$stream = [System.IO.File]::OpenWrite($icoPath)
$icon.Save($stream)
$stream.Close()
$icon.Dispose()
$bmp.Dispose()

Write-Host "ICO created at: $icoPath"

# Now update the shortcut
$WshShell = New-Object -ComObject WScript.Shell
$lnk = $WshShell.CreateShortcut("C:\Users\user\Desktop\Ziyo_CRM_Start.lnk")
$lnk.TargetPath = "C:\Users\user\.gemini\antigravity\scratch\DOKTOR_SCHOOL\Start.bat"
$lnk.WorkingDirectory = "C:\Users\user\.gemini\antigravity\scratch\DOKTOR_SCHOOL"
$lnk.IconLocation = $icoPath + ",0"
$lnk.Description = "ZiyoCRM - Ta'lim markazi boshqaruv tizimi"
$lnk.Save()
Write-Host "Shortcut updated with new ZiyoCRM logo!"
