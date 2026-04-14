<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageConversionService
{
    /**
     * Convert an uploaded image to WebP and persist it via Laravel Storage.
     *
     * - Preserves original pixel dimensions (no resize).
     * - Preserves alpha channel for PNG/WebP sources.
     * - Falls back to storing the original file if the source MIME is not
     *   a raster image GD can decode (e.g. SVG).
     *
     * @param  UploadedFile  $file       The incoming upload.
     * @param  string        $directory  Storage directory (e.g. "settings").
     * @param  string        $baseName   File name WITHOUT extension (e.g. "logo-1713091200").
     * @param  string        $disk       Laravel storage disk (default "public").
     * @param  int           $quality    WebP quality 0-100; pass IMG_WEBP_LOSSLESS (101) for lossless.
     * @return string  The stored path relative to the disk root.
     */
    public static function storeAsWebP(
        UploadedFile $file,
        string $directory,
        string $baseName,
        string $disk = 'public',
        int $quality = 90
    ): string {
        $mime       = $file->getMimeType() ?? '';
        $sourcePath = $file->getRealPath();

        $image = match (true) {
            str_contains($mime, 'png')  => imagecreatefrompng($sourcePath),
            str_contains($mime, 'jpeg') => imagecreatefromjpeg($sourcePath),
            str_contains($mime, 'gif')  => imagecreatefromgif($sourcePath),
            str_contains($mime, 'webp') => imagecreatefromwebp($sourcePath),
            str_contains($mime, 'bmp')  => imagecreatefrombmp($sourcePath),
            default                     => false,
        };

        if ($image === false) {
            // Non-raster or unrecognised format — store as original.
            return $file->storeAs($directory, $baseName . '.' . $file->getClientOriginalExtension(), $disk);
        }

        // Ensure true-colour canvas so transparency survives the round-trip.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Encode to WebP in memory, then push through Storage so all disk
        // drivers (local, S3, …) are supported without touching the filesystem directly.
        ob_start();
        imagewebp($image, null, $quality);
        $webpData = ob_get_clean();
        imagedestroy($image);

        $storedPath = $directory . '/' . $baseName . '.webp';
        Storage::disk($disk)->put($storedPath, $webpData);

        return $storedPath;
    }
}
