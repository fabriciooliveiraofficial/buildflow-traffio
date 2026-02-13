<?php
/**
 * PWA Icon Generator
 * 
 * Generates placeholder icons for PWA manifest.
 * Run this script to create all required icon sizes.
 * 
 * Usage: php generate-icons.php
 */

$sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
$outputDir = __DIR__ . '/assets/icons';

// Create directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Brand colors
$bgColor = '#2196f3';
$textColor = '#ffffff';

foreach ($sizes as $size) {
    $fontSize = floor($size * 0.5);
    $textY = floor($size * 0.65);
    $radius = floor($size * 0.15);

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
    <rect fill="{$bgColor}" width="{$size}" height="{$size}" rx="{$radius}"/>
    <text x="{$half}" y="{$textY}" text-anchor="middle" fill="{$textColor}" font-size="{$fontSize}" font-weight="bold" font-family="Arial, sans-serif">B</text>
</svg>
SVG;

    // Fix the half calculation in SVG
    $half = floor($size / 2);
    $svg = str_replace('{$half}', $half, $svg);

    // Save as SVG
    $filename = $outputDir . "/icon-{$size}x{$size}.svg";
    file_put_contents($filename, $svg);
    echo "Created: icon-{$size}x{$size}.svg\n";

    // Try to convert to PNG using GD if available
    if (extension_loaded('gd')) {
        $image = imagecreatetruecolor($size, $size);

        // Enable alpha blending
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Parse colors
        $bg = imagecolorallocate($image, 0x21, 0x96, 0xf3);
        $white = imagecolorallocate($image, 255, 255, 255);

        // Fill background with rounded corners (simplified - just fill)
        imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $bg);

        // Add text "B" in center
        $fontFile = null; // Would need a TTF font file for proper text

        // Save as PNG
        $pngFilename = $outputDir . "/icon-{$size}x{$size}.png";
        imagepng($image, $pngFilename);
        imagedestroy($image);
        echo "Created: icon-{$size}x{$size}.png\n";
    }
}

// Create shortcut icons
$shortcuts = ['dashboard', 'project', 'clock', 'expense'];
foreach ($shortcuts as $shortcut) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    <rect fill="{$bgColor}" width="96" height="96" rx="14"/>
    <text x="48" y="62" text-anchor="middle" fill="{$textColor}" font-size="48" font-weight="bold" font-family="Arial, sans-serif">B</text>
</svg>
SVG;

    $filename = $outputDir . "/shortcut-{$shortcut}.svg";
    file_put_contents($filename, $svg);
    echo "Created: shortcut-{$shortcut}.svg\n";
}

// Create badge icon
$badgeSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 72 72">
    <circle cx="36" cy="36" r="36" fill="{$bgColor}"/>
    <text x="36" y="46" text-anchor="middle" fill="{$textColor}" font-size="36" font-weight="bold" font-family="Arial, sans-serif">B</text>
</svg>
SVG;

file_put_contents($outputDir . '/badge-72x72.svg', $badgeSvg);
echo "Created: badge-72x72.svg\n";

echo "\nDone! Icons created in: " . realpath($outputDir) . "\n";
echo "\nNote: For production, replace these with properly designed PNG icons.\n";
echo "Recommended: Use a tool like https://realfavicongenerator.net/\n";
