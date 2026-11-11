<?php

namespace App\Support\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validates an uploaded report photo by its real contents, strips all metadata
 * (EXIF/GPS/device info), downsizes it, and re-encodes it as WebP
 * (JPEG if this PHP build has no WebP support).
 */
class ReportPhotoProcessor
{
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_EDGE = 1600;
    private const QUALITY = 80;
    private const MAX_PIXELS = 20_000_000; // guards against decompression bombs

    public function store(UploadedFile $file, string $directory = 'report-evidence', string $disk = 'local'): string
    {
        $path = $file->getRealPath();

        // Trust the file's contents, never the client-supplied name or MIME type.
        $info = @getimagesize($path);
        if (! $info || ! in_array($info['mime'], self::ALLOWED, true)) {
            $this->fail('This file is not a valid JPG, PNG, or WebP image.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            $this->fail("This photo's resolution is too high. Choose a smaller photo.");
        }

        $src = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
        };
        if (! $src) {
            $this->fail('This image could not be read. Try a different photo.');
        }

        // Apply the camera's rotation before metadata is dropped.
        if ($info['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = is_array($exif) ? ($exif['Orientation'] ?? 1) : 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0 && ($rotated = imagerotate($src, $angle, 0))) {
                imagedestroy($src);
                $src = $rotated;
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, self::MAX_EDGE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        // A fresh canvas drops EXIF (GPS, device info) and anything hidden in the original.
        $dst = imagecreatetruecolor($nw, $nh);

        // Keep transparency from PNG/WebP sources instead of filling it with a color.
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        [$bytes, $ext] = $this->encode($dst);
        imagedestroy($src);
        imagedestroy($dst);

        $name = $directory . '/' . Str::uuid() . '.' . $ext;
        if (! $bytes || ! Storage::disk($disk)->put($name, $bytes)) {
            $this->fail('The photo could not be saved. Try again or continue without it.');
        }

        return $name;
    }

    /** @return array{0: string|false, 1: string} bytes and file extension */
    private function encode(\GdImage $image): array
    {
        if ($this->supportsWebp()) {
            ob_start();
            imagewebp($image, null, self::QUALITY);
            $bytes = ob_get_clean();
            if ($bytes) {
                return [$bytes, 'webp'];
            }
        }

        // Fallback if GD was built without WebP: flatten onto white and save as JPEG.
        $w = imagesx($image);
        $h = imagesy($image);
        $flat = imagecreatetruecolor($w, $h);
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, $w, $h);

        ob_start();
        imagejpeg($flat, null, self::QUALITY);
        $bytes = ob_get_clean();
        imagedestroy($flat);

        return [$bytes, 'jpg'];
    }

    private function supportsWebp(): bool
    {
        return function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['photo' => [$message]]);
    }
}