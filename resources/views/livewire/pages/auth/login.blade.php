<?php

use App\Http\Middleware\EnsureSchoolLicenceIsActive;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use App\Support\DemoAccounts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-school-login')] class extends Component
{
    public string $school_number = '';

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public ?string $demoRole = null;

    public ?string $demoSchoolType = null;

    public function mount(): void
    {
        $role = (string) request()->query('demo', '');
        $account = DemoAccounts::role($role);
        if (! is_array($account)) {
            return;
        }

        $this->demoRole = $role;
        $schoolType = (string) request()->query('school_type', '');
        if (! is_array(DemoAccounts::schoolType($schoolType))) {
            return;
        }

        $this->demoSchoolType = $schoolType;
        $this->school_number = DemoAccounts::schoolNumber($schoolType);
        $this->email = (string) $account['email'];
        $this->password = DemoAccounts::password();
    }

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
                    ? EnsureSchoolLicenceIsActive::EXPIRED_MESSAGE
                    : EnsureSchoolLicenceIsActive::INACTIVE_MESSAGE,
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->canLogin()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => in_array($user->role, ['student', 'parent'], true)
                    ? 'This account cannot sign in because the linked learner is inactive. Please contact your school administrator.'
                    : 'This account is inactive. Please contact your school administrator.',
            ]);
        }

        if ($user->school_id !== $school->id) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            throw $genericError();
        }

        RateLimiter::clear($this->throttleKey());

        $currentIp = request()->ip();
        $otpEnabled = SchoolSetting::where(['school_id' => $school->id, 'key' => 'otp_enabled'])->value('value') === 'enabled';
        $isSuspicious = $otpEnabled || ($user->last_login_ip !== null && $user->last_login_ip !== $currentIp);

        $isDemoAccount = DemoAccounts::includes($school->school_number, $user->email);

        if (config('app.otp_force')) {
            $isSuspicious = true;
        }

        if ($isSuspicious && ! $isDemoAccount) {
            $code = $user->generateOtp();
            $user->notify(new OtpCodeNotification($code));

            Auth::logout();
            session(['otp_pending_user_id' => $user->id, 'otp_remember' => $this->remember]);

            $this->redirect(route('otp.verify', absolute: false), navigate: true);

            return;
        }

        session()->regenerate();
        session()->put('active_school_id', $school->id);
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

<div class="school-login">
    <p class="login-eyebrow text-xs font-bold uppercase tracking-[0.2em] text-yellow-600">School portal</p>
    <h1 class="mt-2 text-[28px] font-extrabold tracking-tight text-[#252641] sm:text-4xl">Welcome back</h1>
    <p class="mb-6 mt-2 text-sm leading-6 text-slate-500 sm:mb-7 sm:mt-3">Your school day starts here. Enter your details to continue.</p>

    @if($demoRole && !$demoSchoolType)
        <div class="mb-6">
            <div class="rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-slate-900">
                <p class="font-semibold">{{ DemoAccounts::role($demoRole)['label'] }} demo selected</p>
                <p class="mt-1 text-xs text-slate-600">Choose the kind of school you want to explore. Each option includes two populated branches.</p>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @php($trialAvatarSettings = \App\Models\LandingPageSetting::values())
                @foreach(DemoAccounts::schoolTypes() as $type => $option)
                    <a href="{{ route('login', ['demo' => $demoRole, 'school_type' => $type]) }}" class="school-type-choice group rounded-2xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-yellow-400 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            @include('partials.trial-avatar', ['avatarKey' => 'school_type_'.$type.'_portrait', 'avatarIndex' => ['kindergarten' => 6, 'primary' => 7, 'secondary' => 8, 'vocational' => 9][$type] ?? 7, 'avatarClass' => 'school-type-portrait', 'avatarSettings' => $trialAvatarSettings])
                            <span><b class="block text-sm text-slate-900">{{ $option['label'] }}</b><span class="mt-1 block text-[11px] leading-4 text-slate-500">{{ $option['description'] }}</span></span>
                        </div>
                        <span class="mt-3 block text-xs font-bold text-yellow-700">Explore this demo <span class="inline-block transition group-hover:translate-x-1">→</span></span>
                    </a>
                @endforeach
            </div>
        </div>
    @elseif($demoRole && $demoSchoolType)
        <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
            <p class="font-semibold">{{ DemoAccounts::schoolType($demoSchoolType)['icon'] }} {{ DemoAccounts::role($demoRole)['label'] }} · {{ DemoAccounts::schoolType($demoSchoolType)['label'] }}</p>
            <p class="mt-1 text-xs text-yellow-800">The credentials are ready. Log in, then use the branch switcher to move between its two campuses.</p>
            <a href="{{ route('login', ['demo' => $demoRole]) }}" class="mt-2 inline-flex text-xs font-bold text-yellow-900 underline">Choose another school type</a>
        </div>
    @endif

    <form wire:submit="login" class="space-y-4" @if($demoRole && !$demoSchoolType) hidden @endif>
        <div>
            <label for="school-number" class="mb-2 block text-xs font-bold text-slate-700">School number</label>
            <div class="login-input-wrap relative">
                <svg class="login-field-icon absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7v11H3V10Zm6 11v-7h6v7M7 10h.01M17 10h.01"/></svg>
                <input id="school-number" name="school_number" type="text" wire:model.fill="school_number" required autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="e.g. EDL-4K9P2"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-base uppercase tracking-wide outline-none transition placeholder:text-slate-300 focus:border-yellow-400 focus:bg-white focus:ring-4 focus:ring-yellow-400/10 sm:py-3.5">
            </div>
            @error('school_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="login-email" class="mb-2 block text-xs font-bold text-slate-700">Email address</label>
            <div class="login-input-wrap relative">
                <svg class="login-field-icon absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/></svg>
                <input id="login-email" name="email" type="email" wire:model="email" required autocomplete="email" autocapitalize="none" spellcheck="false" inputmode="email" placeholder="you@school.com"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-base outline-none transition placeholder:text-slate-300 focus:border-yellow-400 focus:bg-white focus:ring-4 focus:ring-yellow-400/10 sm:py-3.5">
            </div>
            @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="login-password" class="mb-2 block text-xs font-bold text-slate-700">Password</label>
            <div class="login-password-field relative" x-data="{ visible: false }">
                <svg class="login-field-icon absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg>
                <input id="login-password" name="password" :type="visible ? 'text' : 'password'" wire:model="password" required autocomplete="current-password" placeholder="Enter your password"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-12 text-base outline-none transition placeholder:text-slate-300 focus:border-yellow-400 focus:bg-white focus:ring-4 focus:ring-yellow-400/10 sm:py-3.5">

                <button class="login-password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none" type="button" @click="visible = !visible" :aria-label="visible ? 'Hide password' : 'Show password'" :aria-pressed="visible.toString()">
                    <svg x-show="!visible" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="visible" style="display: none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                </button>
            </div>
            @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
            <label class="flex min-h-11 cursor-pointer items-center space-x-2 text-xs font-medium text-slate-500 sm:text-sm">
                <input type="checkbox" wire:model="remember" class="h-4 w-4 shrink-0 rounded border-slate-300 text-yellow-500 focus:ring-yellow-400">
                <span>Remember me</span>
            </label>
            <a href="{{ route('password.request') }}" wire:navigate class="inline-flex min-h-11 items-center text-xs font-bold text-yellow-700 hover:text-yellow-800 sm:text-sm">Forgot password?</a>
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="login"
            class="login-submit group flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-3.5 font-bold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-slate-800">
            <span wire:loading.remove wire:target="login">Log in to Edlink</span>
            <svg wire:loading.remove wire:target="login" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>
            <span wire:loading wire:target="login">Signing in…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        No account yet?
        <a href="{{ route('register') }}" wire:navigate class="font-bold text-yellow-600 hover:text-yellow-700">Start a free demo</a>
    </p>
</div>
