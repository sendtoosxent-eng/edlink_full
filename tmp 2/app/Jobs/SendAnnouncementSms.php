<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\User;
use App\Services\SchoolSmsSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnnouncementSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $announcementId,
        public readonly int $userId,
    ) {}

    public function handle(SchoolSmsSender $sender): void
    {
        $announcement = Announcement::find($this->announcementId);
        $user = User::find($this->userId);

        if (! $announcement || ! $user || $user->school_id !== $announcement->school_id || blank($user->phone)) return;

        $sender->send($user->school, $user->phone, $announcement->title.': '.$announcement->message);
    }
}
