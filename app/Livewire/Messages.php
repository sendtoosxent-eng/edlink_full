<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\User;
use App\Support\ChatAccess;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Messages extends Component
{
    use WithFileUploads;

    public array $attachments = [];

    public array $uploads = [];

    #[Locked]
    public int $uploadRevision = 0;

    #[Locked]
    public int $schoolId;

    #[Locked]
    public ?int $peerId = null;

    #[Locked]
    public string $clientToken;

    #[Locked]
    public int $messageLimit = 50;

    public string $search = '';

    public string $body = '';

    public function mount(): void
    {
        $this->schoolId = ChatAccess::actor()->school_id;
        $this->clientToken = (string) Str::uuid();
    }

    private function actor(): User
    {
        $user = ChatAccess::actor();
        abort_unless((int) $user->school_id === $this->schoolId, 403, 'School changed. Reload Messages to continue.');

        return $user;
    }

    private function peer(User $user): User
    {
        return ChatAccess::contacts($user)->findOrFail($this->peerId);
    }

    public function openConversation(int $userId): void
    {
        $user = $this->actor();
        ChatAccess::contacts($user)->findOrFail($userId);
        $this->peerId = $userId;
        $this->body = '';
        $this->clearAttachments();
        $this->messageLimit = 50;
        $this->clientToken = (string) Str::uuid();
        $this->resetValidation();
    }

    public function closeConversation(): void
    {
        $this->peerId = null;
        $this->body = '';
        $this->clearAttachments();
    }

    private function attachmentRules(): array
    {
        return [
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp,gif',
                'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp,gif'],
        ];
    }

    public function updatedUploads(): void
    {
        $this->actor();
        $this->resetValidation();
        $this->validate(collect($this->attachmentRules())->mapWithKeys(fn ($rules, $key) => [str_replace('attachments', 'uploads', $key) => $rules])->all());
        if (count($this->attachments) + count($this->uploads) > 5) {
            $this->uploads = [];
            $this->uploadRevision++;
            $this->addError('attachments', 'You can attach up to 5 files per message.');

            return;
        }
        $this->attachments = array_merge($this->attachments, $this->uploads);
        $this->uploads = [];
        $this->uploadRevision++;
        $this->validate($this->attachmentRules());
    }

    public function removeAttachment(int $index): void
    {
        $this->actor();
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
        $this->resetValidation();
    }

    private function clearAttachments(): void
    {
        $this->attachments = [];
        $this->uploads = [];
        $this->uploadRevision++;
    }

    public function loadOlder(): void
    {
        $this->actor();
        $this->messageLimit = min(500, $this->messageLimit + 50);
    }

    public function send(): void
    {
        $user = $this->actor();
        $peer = $this->peer($user);
        $this->body = trim($this->body);
        $this->validate(['body' => ['required_without:attachments', 'string', 'max:5000']] + $this->attachmentRules());
        $key = 'chat-send:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            $this->addError('body', 'Please wait a moment before sending more messages.');

            return;
        }
        RateLimiter::hit($key, 60);
        $files = [];
        try {
            foreach ($this->attachments as $attachment) {
                $path = 'chat-attachments/'.$this->schoolId.'/'.Str::uuid().'.'.strtolower($attachment->getClientOriginalExtension());
                $files[] = ['path' => $path, 'name' => mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $attachment->getClientOriginalName()))), 0, 200),
                    'mime' => $attachment->getMimeType(), 'size' => $attachment->getSize()];
                $stream = $attachment->readStream();
                try {
                    $stored = Storage::disk('local')->put($path, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
                throw_unless($stored, \RuntimeException::class, 'The attachment could not be stored. Please try again.');
            }
            $message = ChatMessage::firstOrCreate(['sender_id' => $user->id, 'client_token' => $this->clientToken], [
                'school_id' => $this->schoolId, 'recipient_id' => $peer->id, 'body' => $this->body, 'attachments' => $files ?: null,
            ]);
            if (! $message->wasRecentlyCreated) {
                Storage::disk('local')->delete(array_column($files, 'path'));
            }
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_column($files, 'path'));
            report($exception);
            $this->addError('attachments', 'Your message could not be sent. Please try again.');

            return;
        }
        $this->body = '';
        $this->clearAttachments();
        $this->resetValidation();
        $this->clientToken = (string) Str::uuid();
        $this->dispatch('chat-sent');
    }

    public function render()
    {
        $user = $this->actor();
        $contacts = ChatAccess::contacts($user)
            ->when(trim($this->search) !== '', fn ($q) => $q->where('name', 'like', '%'.mb_substr(trim($this->search), 0, 100).'%'))
            ->addSelect(['last_message_id' => ChatMessage::query()->select('id')
                ->where('school_id', $this->schoolId)
                ->where(fn ($q) => $q->where(fn ($q) => $q->where('sender_id', $user->id)->whereColumn('recipient_id', 'users.id'))
                    ->orWhere(fn ($q) => $q->where('recipient_id', $user->id)->whereColumn('sender_id', 'users.id')))
                ->latest('id')->limit(1)])
            ->addSelect(['unread_count' => ChatMessage::query()->selectRaw('count(*)')
                ->where('school_id', $this->schoolId)->where('recipient_id', $user->id)
                ->whereColumn('sender_id', 'users.id')->whereNull('read_at')])
            ->orderByDesc('last_message_id')->orderBy('name')->limit(50)->get();

        $peer = null;
        $messages = collect();
        $hasOlder = false;
        if ($this->peerId) {
            $peer = $this->peer($user);
            $messages = ChatMessage::between($this->schoolId, $user->id, $peer->id)
                ->latest('id')->limit($this->messageLimit + 1)->get();
            $hasOlder = $messages->count() > $this->messageLimit;
            $messages = $messages->take($this->messageLimit)->reverse()->values();
            // Only acknowledge messages actually included in this response.
            ChatMessage::whereIn('id', $messages->pluck('id'))->where('recipient_id', $user->id)
                ->whereNull('read_at')->update(['read_at' => now()]);
            $remainingUnread = ChatMessage::between($this->schoolId, $user->id, $peer->id)
                ->where('recipient_id', $user->id)->whereNull('read_at')->count();
            $contacts->where('id', $peer->id)->each(fn ($contact) => $contact->unread_count = $remainingUnread);
        }

        return view('livewire.messages', compact('contacts', 'peer', 'messages', 'hasOlder', 'user'))->title('Messages');
    }
}
