<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $key = $user ? "school:{$user->school_id}:user:{$user->id}" : $request->ip();

            return Limit::perMinute(120)->by($key);
        });

        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            $notifications = collect();

            if ($user && $user->school_id && Schema::hasTable('school_notifications') && Schema::hasTable('school_notification_reads')) {
                $notifications = DB::table('school_notifications as notifications')
                    ->leftJoin('school_notification_reads as reads', function ($join) use ($user): void {
                        $join->on('reads.school_notification_id', '=', 'notifications.id')
                            ->where('reads.user_id', $user->id);
                    })
                    ->where('notifications.school_id', $user->school_id)
                    ->where(function ($query) use ($user): void {
                        $query->whereNull('notifications.user_id')->orWhere('notifications.user_id', $user->id);
                    })
                    ->latest('notifications.created_at')
                    ->limit(10)
                    ->get(['notifications.id', 'notifications.title', 'notifications.message', 'notifications.type', 'reads.read_at', 'notifications.created_at']);
            }

            $view->with('layoutNotifications', $notifications);
        });
    }
}
