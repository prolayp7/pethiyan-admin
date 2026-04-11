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
     * Convert an uploaded image file to WebP and return a temporary file path.
     * Non-image files (PDFs, videos, etc.) are returned unchanged.
     *
     * @param  UploadedFile  $file
     * @param  int           $quality  WebP quality 1–100 (default 85)
     * @return array{path: string, filename: string, isWebp: bool}
     */
    public static function convert(UploadedFile $file, int $quality = 85): array
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

        // If already WebP, still run through GD to normalise and re-encode
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
