$sh = New-Object -ComObject WScript.Shell
$target = "C:\Users\user\Desktop\Ziyo_CRM_Start.lnk"
if (Test-Path $target) {
    $lnk = $sh.CreateShortcut($target)
    Write-Host "Target:" $lnk.TargetPath
    Write-Host "Arguments:" $lnk.Arguments
    Write-Host "WorkingDirectory:" $lnk.WorkingDirectory
    Write-Host "IconLocation:" $lnk.IconLocation
}
