<?php

namespace App\Providers;

use App\Livewire\FeeAdjustments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::setUpdateRoute(function ($handle, $path) {
            return Route::post($path, $handle)
                ->middleware(['web', 'auth', 'branch.context', 'active.user'])
                ->name('school.livewire.update');
        });

        // Some deployments retain the previous route cache after pulling new code.
        // Keep this newly introduced finance screen reachable until that cache is rebuilt.
        if ($this->app->routesAreCached() && ! Route::has('fee-adjustments.index')) {
            Route::middleware(['web', 'auth', 'verified', 'branch.context', 'active.user', 'designation.access'])
                ->get('finance/fee-adjustments', FeeAdjustments::class)
                ->name('fee-adjustments.index');
        }

        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $key = $user ? "school:{$user->school_id}:user:{$user->id}" : $request->ip();

            return Limit::perMinute(120)->by($key);
        });

        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            $notifications = collect();

            if ($user && $user->school_id && Schema::hasTable('school_notifications')) {
                $query = DB::table('school_notifications as notifications')
                    ->when(Schema::hasTable('school_notification_reads'), function ($query) use ($user): void {
                        $query->leftJoin('school_notification_reads as reads', function ($join) use ($user): void {
                        $join->on('reads.school_notification_id', '=', 'notifications.id')
                            ->where('reads.user_id', $user->id);
                        });
                    })
                    ->where('notifications.school_id', $user->school_id)
                    ->where(function ($query) use ($user): void {
                        $query->whereNull('notifications.user_id')->orWhere('notifications.user_id', $user->id);
                    })
                    ->latest('notifications.created_at')
                    ->limit(10);

                $notifications = $query->get([
                    'notifications.id', 'notifications.title', 'notifications.message',
                    'notifications.type', 'notifications.created_at',
                    Schema::hasTable('school_notification_reads') ? 'reads.read_at' : 'notifications.read_at',
                ]);
            }

            $view->with('layoutNotifications', $notifications);
        });
    }
}
