<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-school-login')] class extends Component
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
        $key = 'otp-verify|'.(int) session('otp_pending_user_id').'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('code', 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.');

            return;
        }

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
        if (! $user || ! $user->canLogin()) {
            session()->forget(['otp_pending_user_id', 'otp_remember']);
            $this->addError('code', 'This account is inactive or linked only to inactive learners. Please contact your school administrator.');

            return;
        }
        if (! $user || ! $user->otpIsValid($this->code)) {
            RateLimiter::hit($key, 60);
            $this->addError('code', 'That code is invalid or has expired.');

            return;
        }

        RateLimiter::clear($key);
        $user->clearOtp();
        // The OTP was delivered to this address, so successfully entering it
        // also proves email ownership. Do not send the user through a second
        // email-verification screen after the same verification event.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
        $user->forceFill(['last_login_ip' => request()->ip()])->save();

        Auth::login($user, (bool) session('otp_remember', false));
        session()->regenerate();
        session()->forget(['otp_pending_user_id', 'otp_remember']);
        session()->put('active_school_id', $user->school_id);

        $this->redirect(route($user->portalHomeRoute(), absolute: false), navigate: false);
    }

    public function resend(): void
    {
        $user = User::find(session('otp_pending_user_id'));
        $key = 'otp-resend|'.(int) session('otp_pending_user_id').'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('code', 'Please wait '.RateLimiter::availableIn($key).' seconds before requesting another code.');

            return;
        }

        if ($user && $user->canLogin() && $user->school?->isLicenceUsable() && ! $user->school->isExpiredDemo()) {
            RateLimiter::hit($key, 60);
            $code = $user->generateOtp();
            $user->notify(new \App\Notifications\OtpCodeNotification($code));
            session()->flash('status', 'A new code has been sent.');
        }
    }
}; ?>

<div class="school-login otp-verification">
    <p class="login-eyebrow">Account verification</p>
    <h1>Verify it’s you</h1>
    <p>Enter the 6-digit code we emailed you to securely continue to your school workspace.</p>

    @if(session('status'))
        <div class="recovery-status" role="status">{{ session('status') }}</div>
    @endif

    <form wire:submit="verify" class="space-y-4">
        <div>
            <label for="login-otp-code" class="block">Verification code</label>
            <input id="login-otp-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" wire:model="code" required autofocus placeholder="000000" class="otp-code-input" @error('code') aria-invalid="true" aria-describedby="otp-code-error" @enderror>
            @error('code')<span id="otp-code-error" class="mt-1 block text-xs text-red-600" role="alert">{{ $message }}</span>@enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" wire:target="verify,resend" class="flex w-full items-center justify-between gap-2">
            <span wire:loading.remove wire:target="verify">Verify &amp; continue</span><span wire:loading wire:target="verify">Verifying…</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>
        </button>
    </form>

    <div class="otp-resend-row"><span>Didn’t receive a code?</span><button type="button" wire:click="resend" wire:loading.attr="disabled" wire:target="resend,verify"><span wire:loading.remove wire:target="resend">Resend code</span><span wire:loading wire:target="resend">Sending…</span></button></div>
    <p>Need to use another account? <a href="{{ route('login') }}" wire:navigate>Back to login</a></p>
</div>
