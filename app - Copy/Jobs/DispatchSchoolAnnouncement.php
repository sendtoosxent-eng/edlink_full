<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\School;
use App\Models\User;
use App\Notifications\QueuedAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchSchoolAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public readonly int $announcementId) {}

    public function handle(): void
    {
        $announcement = Announcement::find($this->announcementId);
        if (! $announcement) return;
        $school = School::find($announcement->school_id);
        if (! $school) return;

        User::query()->where('school_id', $announcement->school_id)->orderBy('id')
            ->chunkById(200, function ($users) use ($announcement, $school): void {
                foreach ($users as $user) {
                    if ($announcement->send_email && filled($user->email)) {
                        $user->notify(new QueuedAnnouncement(
                            $school->name,
                            $announcement->title,
                            $announcement->message,
                        ));
                    }

                    if ($announcement->send_sms && filled($user->phone)) {
                        SendAnnouncementSms::dispatch($announcement->id, $user->id)->afterCommit();
                    }
                }
            });

        $announcement->update(['delivery_status' => 'dispatched']);
    }
}
