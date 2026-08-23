<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NotificationCenter extends Component
{
    public function markRead(int $notificationId): void
    {
        $user = Auth::user();
        $allowed = DB::table('school_notifications')
            ->where('id', $notificationId)
            ->where('school_id', $user->school_id)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->exists();

        abort_unless($allowed, 404);

        DB::table('school_notification_reads')->updateOrInsert(
            ['school_notification_id' => $notificationId, 'user_id' => $user->id],
            ['read_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function markAllRead(): void
    {
        $user = Auth::user();
        $ids = DB::table('school_notifications')
            ->where('school_id', $user->school_id)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->pluck('id');

        foreach ($ids as $id) {
            DB::table('school_notification_reads')->updateOrInsert(
                ['school_notification_id' => $id, 'user_id' => $user->id],
                ['read_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function render()
    {
        $user = Auth::user();
        $notifications = DB::table('school_notifications as notifications')
            ->leftJoin('school_notification_reads as reads', function ($join) use ($user): void {
                $join->on('reads.school_notification_id', '=', 'notifications.id')
                    ->where('reads.user_id', $user->id);
            })
            ->where('notifications.school_id', $user->school_id)
            ->where(fn ($query) => $query->whereNull('notifications.user_id')->orWhere('notifications.user_id', $user->id))
            ->latest('notifications.created_at')
            ->get([
                'notifications.id', 'notifications.title', 'notifications.message',
                'notifications.type', 'notifications.created_at', 'reads.read_at',
            ]);

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'unreadCount' => $notifications->whereNull('read_at')->count(),
        ])->title('Notification Center');
    }
}
