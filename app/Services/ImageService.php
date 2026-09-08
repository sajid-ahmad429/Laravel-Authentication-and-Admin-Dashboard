<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Upload, resize, and convert image to WebP with optimized compression.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality
     * @return string Relative URL/path of stored file
     */
    public function uploadAndOptimize(
        UploadedFile $file,
        string $folder = 'avatars',
        int $maxWidth = 500,
        int $maxHeight = 500,
        int $quality = 80
    ): string {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('The uploaded file is invalid.');
        }

        // Restrict storage to a safe folder name (no traversal).
        $folder = trim($folder, '/');
        if ($folder === '' || ! preg_match('#^[A-Za-z0-9_\-/]+$#', $folder) || str_contains($folder, '..')) {
            throw new \InvalidArgumentException('Invalid storage folder.');
        }

        $filename = Str::uuid().'.webp';
        $destinationPath = "public/{$folder}/".$filename;

        try {
            // Process image using Intervention Image v3
            $image = $this->manager->read($file->getRealPath());

            // Scale/crop down while maintaining aspect ratio
            $image->cover($maxWidth, $maxHeight);

            // Encode to WebP format
            $encoded = $image->toWebp($quality);

            Storage::put($destinationPath, (string) $encoded);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not process the uploaded image: '.$e->getMessage(), 0, $e);
        }

        return "storage/{$folder}/".$filename;
    }

    /**
     * Delete existing image file safely if present.
     *
     * @param string|null $path
     * @return bool
     */
    public function deleteImage(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $relativePath = str_replace('storage/', 'public/', $path);

        if (Storage::exists($relativePath)) {
            return Storage::delete($relativePath);
        }

        return false;
    }
}
