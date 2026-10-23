<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StaffUploadService
{
    public static function rules(): array
    {
        return [
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'qualification_documents' => ['nullable', 'array', 'max:5'],
            'qualification_documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function persist(int $schoolId, ?UploadedFile $photo, array $documents, callable $save): mixed
    {
        $photoPath = null;
        $paths = [];
        try {
            if ($photo) $photoPath = app(PublicImageStorage::class)->store($photo, 'avatars/'.$schoolId);
            $rows = [];
            foreach ($documents as $file) {
                $path = $file->store('staff-documents/'.$schoolId, 'local');
                if (! is_string($path) || $path === '') throw new RuntimeException('The qualification document could not be stored.');
                $paths[] = $path;
                $rows[] = ['school_id' => $schoolId, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()];
            }

            return DB::transaction(fn () => $save($photoPath, $rows));
        } catch (Throwable $exception) {
            if ($photoPath) Storage::disk('public')->delete($photoPath);
            if ($paths) Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }
}
