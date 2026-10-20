<?php

namespace App\Http\Controllers;

use App\Models\PlatformNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlatformNotificationController extends Controller
{
    public function index(): View
    {
        $notifications = PlatformNotification::where('platform_admin_id', Auth::guard('platform')->id())->latest()->latest('id')->paginate(25);

        return view('platform.notifications', compact('notifications'));
    }

    public function read(PlatformNotification $notification): RedirectResponse
    {
        abort_unless($notification->platform_admin_id === Auth::guard('platform')->id(), 404);
        if (! $notification->read_at) $notification->update(['read_at' => now()]);

        return back();
    }
}
