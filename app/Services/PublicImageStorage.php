<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PublicImageStorage
{
    public function store(UploadedFile $image, string $directory): string
    {
        $path = $image->store(trim($directory, '/'), 'public');

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path) || Storage::disk('public')->size($path) < 1) {
            throw new RuntimeException('The image could not be saved to permanent storage. Your existing image was not changed.');
        }

        return $path;
    }

    public function deleteReplacement(?string $oldPath, string $newPath): void
    {
        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
