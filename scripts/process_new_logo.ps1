Add-Type -AssemblyName System.Drawing

$newLogoSrc = "C:\Users\user\.gemini\antigravity\brain\8e014e3b-ee6c-444d-8d3a-c7c86f7fdf2c\.user_uploaded\media__1785311358275.jpg"
$assetsDir  = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets"

# 1. Copy as new main logo
copy-item $newLogoSrc "$assetsDir\ziyo_new_logo.jpg" -Force

$src = [System.Drawing.Bitmap]::FromFile($newLogoSrc)
$w = $src.Width
$h = $src.Height

# Remove dark background and crop exact icon symbol + ZiyoCRM text
# Let's crop tight bounds around non-dark pixels
$minX = $w; $minY = $h; $maxX = 0; $maxY = 0

for ($y = 0; $y -lt $h; $y += 2) {
    for ($x = 0; $x -lt $w; $x += 2) {
        $c = $src.GetPixel($x, $y)
        # If pixel is NOT background dark blue (#040814 ~ R<20, G<25, B<40)
        if ($c.R -gt 25 -or $c.G -gt 35 -or $c.B -gt 55) {
            if ($x -lt $minX) { $minX = $x }
            if ($x -gt $maxX) { $maxX = $x }
            if ($y -lt $minY) { $minY = $y }
            if ($y -gt $maxY) { $maxY = $y }
        }
    }
}

$pad = 12
$minX = [Math]::Max(0, $minX - $pad)
$minY = [Math]::Max(0, $minY - $pad)
$maxX = [Math]::Min($w - 1, $maxX + $pad)
$maxY = [Math]::Min($h - 1, $maxY + $pad)

$cropW = $maxX - $minX + 1
$cropH = $maxY - $minY + 1

Write-Host "Cropping bounds: X=$minX, Y=$minY, W=$cropW, H=$cropH"

$rect = New-Object System.Drawing.Rectangle($minX, $minY, $cropW, $cropH)
$cropped = $src.Clone($rect, $src.PixelFormat)

# Make background transparent
$transparentPng = New-Object System.Drawing.Bitmap($cropW, $cropH)
$g = [System.Drawing.Graphics]::FromImage($transparentPng)
$g.Clear([System.Drawing.Color]::Transparent)

for ($y = 0; $y -lt $cropH; $y++) {
    for ($x = 0; $x -lt $cropW; $x++) {
        $c = $cropped.GetPixel($x, $y)
        # Check if dark background
        if ($c.R -lt 30 -and $c.G -lt 40 -and $c.B -lt 65) {
            # Make transparent
            $transparentPng.SetPixel($x, $y, [System.Drawing.Color]::FromArgb(0, 0, 0, 0))
        } else {
            $transparentPng.SetPixel($x, $y, $c)
        }
    }
}

# Save transparent PNG
$transparentPng.Save("$assetsDir\ziyo_clean_icon.png", [System.Drawing.Imaging.ImageFormat]::Png)

# 2. Make Desktop Shortcut ICO (256x256 with exact icon symbol transparent)
$icoBmp = New-Object System.Drawing.Bitmap(256, 256)
$g2 = [System.Drawing.Graphics]::FromImage($icoBmp)
$g2.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
$g2.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g2.Clear([System.Drawing.Color]::Transparent)

# Fit symbol perfectly inside 256x256
$scale = [Math]::Min(240 / $cropW, 240 / $cropH)
$nw = [int]($cropW * $scale)
$nh = [int]($cropH * $scale)
$nx = [int]((256 - $nw) / 2)
$ny = [int]((256 - $nh) / 2)

$g2.DrawImage($transparentPng, $nx, $ny, $nw, $nh)

$icoFile = "$assetsDir\ziyo_shortcut.ico"
$icon = [System.Drawing.Icon]::FromHandle($icoBmp.GetHicon())
$stream = [System.IO.File]::OpenWrite($icoFile)
$icon.Save($stream)
$stream.Close()

$g.Dispose()
$g2.Dispose()
$src.Dispose()
$cropped.Dispose()
$transparentPng.Dispose()
$icoBmp.Dispose()
$icon.Dispose()

Write-Host "New logo processed and transparent ICO created!"

# Update Desktop shortcut with new clean ICO
$WshShell = New-Object -ComObject WScript.Shell
$lnk = $WshShell.CreateShortcut("C:\Users\user\Desktop\Ziyo_CRM_Start.lnk")
$lnk.TargetPath = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\Start.bat"
$lnk.WorkingDirectory = "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM"
$lnk.IconLocation = $icoFile + ",0"
$lnk.Description = "ZiyoCRM - Ta'lim markazi boshqaruv tizimi"
$lnk.Save()

Write-Host "Desktop shortcut updated with new clean logo!"
