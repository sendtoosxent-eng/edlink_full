<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-split')] class extends Component
{
    public string $code = '';

    public function mount(): void
    {
        if (! session('otp_pending_user_id')) {
            $this->redirect(route('login', absolute: false), navigate: true);
        }
    }

    public function verify(): void
    {
        $this->validate(['code' => ['required', 'digits:6']]);

        $user = User::find(session('otp_pending_user_id'));

        $school = $user?->school;
        if ($user && (! $school || ! $school->isLicenceUsable() || $school->isExpiredDemo())) {
            session()->forget(['otp_pending_user_id', 'otp_remember']);

            $expired = $school && ($school->license_status === 'expired'
                || ($school->license_expires_at && $school->license_expires_at->isPast())
                || $school->isExpiredDemo());

            $this->addError(
                'code',
                $expired
                    ? \App\Http\Middleware\EnsureSchoolLicenceIsActive::EXPIRED_MESSAGE
                    : \App\Http\Middleware\EnsureSchoolLicenceIsActive::INACTIVE_MESSAGE
            );

            return;
        }
        if (! $user || ! $user->otpIsValid($this->code)) {
            $this->addError('code', 'That code is invalid or has expired.');
            return;
        }

        $user->clearOtp();
        $user->forceFill(['last_login_ip' => request()->ip()])->save();

        Auth::login($user, (bool) session('otp_remember', false));
        request()->session()->regenerate();
        session()->forget(['otp_pending_user_id', 'otp_remember']);

        $this->redirect(
            $user->hasVerifiedEmail() ? route($user->portalHomeRoute(), absolute: false) : route('verification.notice', absolute: false),
            navigate: false
        );
    }

    public function resend(): void
    {
        $user = User::find(session('otp_pending_user_id'));

        if ($user) {
            $code = $user->generateOtp();
            $user->notify(new \App\Notifications\OtpCodeNotification($code));
            session()->flash('status', 'A new code has been sent.');
        }
    }
}; ?>

<div>
    <h1 class="text-2xl font-semibold text-darken mb-1">Verify it's you</h1>
    <p class="text-gray-500 text-sm mb-6">We noticed a login from a new device or location. Enter the 6-digit code we emailed you.</p>

    @if (session('status'))
        <p class="text-sm text-green-600 mb-4">{{ session('status') }}</p>
    @endif

    <form wire:submit="verify" class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Verification code</label>
            <input type="text" inputmode="numeric" maxlength="6" wire:model="code" required autofocus
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-2xl tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-yellow-400">
            @error('code') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <button type="submit"
            class="w-full bg-yellow-500 text-darken font-semibold py-3 rounded-full hover:bg-yellow-400 transition">
            Verify &amp; continue
        </button>
    </form>

    <button wire:click="resend" class="w-full text-center text-sm text-gray-500 underline mt-4">
        Resend code
    </button>
</div>
