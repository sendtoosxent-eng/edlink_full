<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            $notifications = collect();

            if ($user && $user->school_id && Schema::hasTable('school_notifications')) {
                $notifications = DB::table('school_notifications')
                    ->where('school_id', $user->school_id)
                    ->where(function ($query) use ($user): void {
                        $query->whereNull('user_id')->orWhere('user_id', $user->id);
                    })
                    ->latest()
                    ->limit(10)
                    ->get(['id', 'title', 'message', 'type', 'read_at', 'created_at']);
            }

            $view->with('layoutNotifications', $notifications);
        });
    }
}
