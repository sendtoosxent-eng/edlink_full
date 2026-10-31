<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Support\ChatAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function __invoke(Request $request, int $message, int $attachment): StreamedResponse
    {
        $user = ChatAccess::actor();
        $chat = ChatMessage::where('school_id', $user->school_id)
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('recipient_id', $user->id))
            ->findOrFail($message);
        $file = $chat->attachments[$attachment] ?? null;
        abort_unless($file && str_starts_with($file['path'], 'chat-attachments/'.$chat->school_id.'/')
            && ! str_contains($file['path'], '..'), 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($file['path']), 404);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($request->boolean('preview')) {
            abort_unless(in_array($file['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true), 404);

            return $disk->response($file['path'], $file['name'], $headers + ['Content-Type' => $file['mime']]);
        }

        return $disk->download($file['path'], $file['name'], $headers + ['Content-Type' => 'application/octet-stream']);
    }
}
