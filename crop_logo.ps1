Add-Type -AssemblyName System.Drawing

function Crop-Image($inputPath, $outputPath) {
    $src = [System.Drawing.Bitmap]::FromFile($inputPath)
    $w = $src.Width
    $h = $src.Height

    # Find bounding box of non-white / non-transparent pixels
    $minX = $w
    $minY = $h
    $maxX = 0
    $maxY = 0

    for ($y = 0; $y -lt $h; $y += 2) {
        for ($x = 0; $x -lt $w; $x += 2) {
            $c = $src.GetPixel($x, $y)
            # Check if pixel is NOT background white or dark
            if ($c.A -gt 20 -and ($c.R -lt 245 -or $c.G -lt 245 -or $c.B -lt 245)) {
                if ($x -lt $minX) { $minX = $x }
                if ($x -gt $maxX) { $maxX = $x }
                if ($y -lt $minY) { $minY = $y }
                if ($y -gt $maxY) { $maxY = $y }
            }
        }
    }

    # Add small padding
    $pad = 10
    $minX = [Math]::Max(0, $minX - $pad)
    $minY = [Math]::Max(0, $minY - $pad)
    $maxX = [Math]::Min($w - 1, $maxX + $pad)
    $maxY = [Math]::Min($h - 1, $maxY + $pad)

    $cropW = $maxX - $minX + 1
    $cropH = $maxY - $minY + 1

    Write-Host "Cropping bounds: X=$minX, Y=$minY, W=$cropW, H=$cropH"

    $rect = New-Object System.Drawing.Rectangle($minX, $minY, $cropW, $cropH)
    $cropped = $src.Clone($rect, $src.PixelFormat)
    $src.Dispose()

    # Make white background transparent
    $cropped.MakeTransparent([System.Drawing.Color]::White)

    $cropped.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $cropped.Dispose()
    Write-Host "Cropped and background removed successfully!"
}

Crop-Image "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\logo.png" "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\logo_clean.png"
Crop-Image "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\brand_header.png" "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\assets\brand_header_clean.png"
