<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageWebpService
{
    /**
     * MIME types that can be converted to WebP.
     */
    private const CONVERTIBLE_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
    ];

    /**
     * Maximum pixel dimension (width or height) for stored images.
     * Images larger than this are downscaled proportionally before encoding.
     */
    private const MAX_DIMENSION = 1920;

    /**
     * Convert an uploaded image file to WebP and return a temporary file path.
     * Non-image files (PDFs, videos, etc.) are returned unchanged.
     *
     * @param  UploadedFile  $file
     * @param  int           $quality    WebP quality 1–100 (default 85)
     * @param  int           $maxDim     Max width or height in pixels (default 1920)
     * @return array{path: string, filename: string, isWebp: bool}
     */
    public static function convert(UploadedFile $file, int $quality = 85, int $maxDim = self::MAX_DIMENSION): array
    {
        $mime = $file->getMimeType();
        $originalName = $file->getClientOriginalName();
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        if (!in_array($mime, self::CONVERTIBLE_TYPES, true)) {
            // Not an image we can convert — return original path as-is
            return [
                'path'     => $file->getRealPath(),
                'filename' => $originalName,
                'isWebp'   => false,
            ];
        }

        // If already WebP, still run through GD to normalise, resize, and re-encode
        $sourcePath = $file->getRealPath();
        $image = self::createGdImage($sourcePath, $mime);

        if ($image === false) {
            // GD failed — return original unchanged
            return [
                'path'     => $sourcePath,
                'filename' => $originalName,
                'isWebp'   => false,
            ];
        }

        // Preserve transparency for PNG/GIF
        if (in_array($mime, ['image/png', 'image/gif'], true)) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        // Downscale if either dimension exceeds the cap
        $image = self::downscale($image, $maxDim);

        $slug      = Str::slug($basename) ?: 'image';
        $tempPath  = sys_get_temp_dir() . '/' . $slug . '_' . uniqid() . '.webp';

        imagewebp($image, $tempPath, $quality);
        imagedestroy($image);

        return [
            'path'     => $tempPath,
            'filename' => $slug . '.webp',
            'isWebp'   => true,
        ];
    }

    /**
     * Downscale a GD image so neither dimension exceeds $maxDim.
     * Returns the original resource untouched if already within bounds.
     *
     * @param  \GdImage  $image
     * @param  int       $maxDim
     * @return \GdImage
     */
    private static function downscale(\GdImage $image, int $maxDim): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);

        if ($w <= $maxDim && $h <= $maxDim) {
            return $image;
        }

        // Proportional scale — longest side becomes $maxDim
        if ($w >= $h) {
            $newW = $maxDim;
            $newH = (int) round($h * $maxDim / $w);
        } else {
            $newH = $maxDim;
            $newW = (int) round($w * $maxDim / $h);
        }

        $resized = imagecreatetruecolor($newW, $newH);

        // Preserve alpha channel for transparent images
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Create a GD image resource from a file path based on its MIME type.
     *
     * @return \GdImage|false
     */
    private static function createGdImage(string $path, string $mime): \GdImage|false
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png'               => @imagecreatefrompng($path),
            'image/gif'               => @imagecreatefromgif($path),
            'image/bmp'               => @imagecreatefrombmp($path),
            'image/webp'              => @imagecreatefromwebp($path),
            default                   => false,
        };
    }
}
