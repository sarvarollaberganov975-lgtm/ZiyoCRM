$WshShell = New-Object -ComObject WScript.Shell
$shortcut = $WshShell.CreateShortcut("C:\Users\user\Desktop\Ziyo_CRM_Start.lnk")
$shortcut.TargetPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\Start.bat"
$shortcut.WorkingDirectory = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM"
$shortcut.IconLocation = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\ziyo_crm.ico,0"
$shortcut.Description = "ZiyoCRM - Ta'lim markazi boshqaruv tizimi"
$shortcut.Save()
Write-Host "Desktop shortcut fixed and pointing to ZiyoCRM folder!"
