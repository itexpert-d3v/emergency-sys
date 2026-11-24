<?php

$sourcePath = __DIR__.'/../public/assets/sou.png';
$outputDir = __DIR__.'/../public/assets';
$source = imagecreatefrompng($sourcePath);

if (!$source) {
    fwrite(STDERR, "Unable to read {$sourcePath}\n");
    exit(1);
}

imagealphablending($source, false);
imagesavealpha($source, true);

foreach ([192, 512] as $size) {
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $scale = min($size / $sourceWidth, $size / $sourceHeight);
    $drawWidth = (int) round($sourceWidth * $scale);
    $drawHeight = (int) round($sourceHeight * $scale);
    $x = (int) (($size - $drawWidth) / 2);
    $y = (int) (($size - $drawHeight) / 2);

    imagecopyresampled($canvas, $source, $x, $y, 0, 0, $drawWidth, $drawHeight, $sourceWidth, $sourceHeight);
    imagepng($canvas, "{$outputDir}/icon-{$size}.png");
    imagedestroy($canvas);
}

$appleSize = 180;
$apple = imagecreatetruecolor($appleSize, $appleSize);
$background = imagecolorallocate($apple, 248, 251, 252);
imagefill($apple, 0, 0, $background);

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);
$scale = min($appleSize / $sourceWidth, $appleSize / $sourceHeight);
$drawWidth = (int) round($sourceWidth * $scale);
$drawHeight = (int) round($sourceHeight * $scale);
$x = (int) (($appleSize - $drawWidth) / 2);
$y = (int) (($appleSize - $drawHeight) / 2);
imagecopyresampled($apple, $source, $x, $y, 0, 0, $drawWidth, $drawHeight, $sourceWidth, $sourceHeight);
imagepng($apple, "{$outputDir}/apple-touch-icon.png");

imagedestroy($apple);
imagedestroy($source);
