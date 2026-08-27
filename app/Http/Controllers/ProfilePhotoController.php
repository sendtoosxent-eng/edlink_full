<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoController extends Controller
{
    public function __invoke(Request $request, string $type, int $person): StreamedResponse
    {
        $path = match ($type) {
            'student' => Student::findOrFail($person)->photo_path,
            'user' => User::findOrFail($person)->avatar_path,
            'school' => School::findOrFail($person)->badge_path,
            default => abort(404),
        };
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
