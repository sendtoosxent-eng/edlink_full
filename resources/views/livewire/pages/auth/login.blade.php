<?php

use App\Models\School;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-split')] class extends Component
{
    public string $school_number = '';
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'school_number' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        $school = School::where('school_number', strtoupper(trim($this->school_number)))->first();

        $genericError = fn () => ValidationException::withMessages([
            'email' => __('These credentials do not match our records.'),
        ]);

        if (! $school || ! Auth::attempt(['school_id' => $school->id, 'email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            throw $genericError();
        }

        if (! $school->isLicenceUsable() || $school->isExpiredDemo()) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            RateLimiter::clear($this->throttleKey());

            $expired = $school->license_status === 'expired'
                || ($school->license_expires_at && $school->license_expires_at->isPast())
                || $school->isExpiredDemo();

            throw ValidationException::withMessages([
                'email' => $expired
                    ? \App\Http\Middleware\EnsureSchoolLicenceIsActive::EXPIRED_MESSAGE
                    : \App\Http\Middleware\EnsureSchoolLicenceIsActive::INACTIVE_MESSAGE,
            ]);
        }
        /** @var User $user */
        $user = Auth::user();

        if ($user->employment_status === 'inactive') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'This account is inactive. Please contact your school administrator.',
            ]);
        }

        if ($user->school_id !== $school->id) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            throw $genericError();
        }

        RateLimiter::clear($this->throttleKey());

        $currentIp = request()->ip();
        $otpEnabled = \App\Models\SchoolSetting::where(['school_id'=>$school->id,'key'=>'otp_enabled'])->value('value') === 'enabled';
        $isSuspicious = $otpEnabled || ($user->last_login_ip !== null && $user->last_login_ip !== $currentIp);

        // Testing aid: set OTP_FORCE=true in .env to always trigger the OTP
        // screen, without needing to fake a different login IP each time.
        if (config('app.otp_force')) {
            $isSuspicious = true;
        }

        if ($isSuspicious) {
            $code = $user->generateOtp();
            $user->notify(new OtpCodeNotification($code));

            Auth::logout();
            session(['otp_pending_user_id' => $user->id, 'otp_remember' => $this->remember]);

            $this->redirect(route('otp.verify', absolute: false), navigate: true);
            return;
        }

        session()->regenerate();
        $user->forceFill(['last_login_ip' => $currentIp])->save();

        $this->redirect(
            $user->hasVerifiedEmail() ? route($user->portalHomeRoute(), absolute: false) : route('verification.notice', absolute: false),
            navigate: false
        );
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::lower($this->email).'|'.request()->ip();
    }
}; ?>

<div>
    <h1 class="text-3xl font-extrabold text-[#252641] tracking-tight">Welcome back</h1>
    <br>
    <p class="text-gray-500 text-sm mb-6">Log in to your school's dashboard.</p>

    <form wire:submit="login" class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">School number</label>
            <input type="text" wire:model="school_number" required autofocus placeholder="e.g. EDL-4K9P2"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 uppercase tracking-wide focus:outline-none focus:ring-2 focus:ring-yellow-400">
            @error('school_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" wire:model="email" required
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
            @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" wire:model="password" required
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
            @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center space-x-2 text-sm text-gray-600">
                <input type="checkbox" wire:model="remember" class="rounded border-gray-300">
                <span>Remember me</span>
            </label>
            <a href='{{ route('password.request') }}' wire:navigate class='text-sm font-medium text-yellow-700 hover:underline'>Forgot password?</a>
        </div>

        <button type="submit"
            class="w-full bg-darken text-white font-medium py-3 rounded-full hover:bg-opacity-90 transition">
            Log in
        </button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-6">
        No account yet?
        <a href="{{ route('register') }}" wire:navigate class="text-yellow-600 font-medium">Start a free demo</a>
    </p>
</div>
