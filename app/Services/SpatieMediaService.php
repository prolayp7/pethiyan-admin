<?php

namespace App\Services;

use Illuminate\Support\Str;

class SpatieMediaService
{
    public static function upload($model, $media)
    {
        $file = request()->file($media);
        if ($file) {
            $converted = ImageWebpService::convert($file);
            $filename  = $converted['filename'];
            $slug      = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
            $ext       = pathinfo($filename, PATHINFO_EXTENSION);
            $sluggedName = $ext ? ($slug . '.' . $ext) : $slug;

            $adder = $model
                ->addMedia($converted['path'])
                ->usingFileName($sluggedName)
                ->toMediaCollection($media);

            if ($converted['isWebp']) {
                @unlink($converted['path']);
            }

            return $adder;
        }

        // Fallback if file is not available for any reason
        return $model->addMediaFromRequest($media)->toMediaCollection($media);
    }

    public static function uploadFromRequest($model, $file, $collectionName)
    {
        $converted   = ImageWebpService::convert($file);
        $filename    = $converted['filename'];
        $slug        = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
        $ext         = pathinfo($filename, PATHINFO_EXTENSION);
        $sluggedName = $ext ? ($slug . '.' . $ext) : $slug;

        $adder = $model
            ->addMedia($converted['path'])
            ->usingFileName($sluggedName)
            ->toMediaCollection($collectionName);

        if ($converted['isWebp']) {
            @unlink($converted['path']);
        }

        return $adder;
    }

    public static function update($request, $model, $media)
    {
        if ($request->hasFile($media)) {
            $file        = $request->file($media);
            $converted   = ImageWebpService::convert($file);
            $filename    = $converted['filename'];
            $slug        = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
            $ext         = pathinfo($filename, PATHINFO_EXTENSION);
            $newImageName = $ext ? ($slug . '.' . $ext) : $slug;

            $existingImage = $model->getFirstMedia($media);

            if (!$existingImage || $existingImage->file_name !== $newImageName) {
                $adder = $model
                    ->addMedia($converted['path'])
                    ->usingFileName($newImageName)
                    ->toMediaCollection($media);

                if ($converted['isWebp']) {
                    @unlink($converted['path']);
                }

                return $adder;
            }

            // Same file — cleanup temp if created
            if ($converted['isWebp']) {
                @unlink($converted['path']);
            }
        }
        return null;
    }
}
