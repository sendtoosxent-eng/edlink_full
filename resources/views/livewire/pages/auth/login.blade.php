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

new #[Layout('layouts.guest-split')] class extends Component
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
        $otpEnabled = SchoolSetting::where(['school_id' => $school->id, 'key' => 'otp_enabled'])->value('value') === 'enabled';
        $isSuspicious = $otpEnabled || ($user->last_login_ip !== null && $user->last_login_ip !== $currentIp);

        // Public demo accounts use shared credentials and do not have inbox
        // access, so OTP would make the advertised demo logins unusable.
        $isDemoAccount = DemoAccounts::includes($school->school_number, $user->email);

        // Testing aid: set OTP_FORCE=true in .env to always trigger the OTP
        // screen, without needing to fake a different login IP each time.
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

<div>
    <p class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-600">School sign in</p>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#252641] sm:text-4xl">Welcome back</h1>
    <p class="mb-7 mt-3 text-sm leading-6 text-slate-500">Enter your school and account details to continue to your workspace.</p>

    @if($demoRole && !$demoSchoolType)
        <div class="mb-6">
            <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-950">
                <p class="font-semibold">{{ DemoAccounts::role($demoRole)['label'] }} demo selected</p>
                <p class="mt-1 text-xs text-violet-800">Choose the kind of school you want to explore. Each option includes two populated branches.</p>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach(DemoAccounts::schoolTypes() as $type => $option)
                    <a href="{{ route('login', ['demo' => $demoRole, 'school_type' => $type]) }}" class="group rounded-2xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-yellow-400 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xl">{{ $option['icon'] }}</span>
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
            <label class="mb-2 block text-xs font-bold text-slate-700">School number</label>
            <input type="text" wire:model.fill="school_number" value="{{ $school_number }}" required autofocus placeholder="e.g. EDL-4K9P2"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 uppercase tracking-wide outline-none transition placeholder:text-slate-300 focus:border-yellow-400 focus:bg-white focus:ring-4 focus:ring-yellow-400/10">
            @error('school_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-bold text-slate-700">Email address</label>
            <input type="email" x-model="$wire.email" required autocomplete="email" placeholder="you@school.com"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 outline-none transition placeholder:text-slate-300 focus:border-yellow-400 focus:bg-white focus:ring-4 focus:ring-yellow-400/10">
            @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-bold text-slate-700">Password</label>
            <input type="password" x-model="$wire.password" required autocomplete="current-password" placeholder="Enter your password"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 outline-none transition placeholder:text-slate-300 focus:border-yellow-400 focus:bg-white focus:ring-4 focus:ring-yellow-400/10">
            @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center justify-between gap-4 pt-1">
            <label class="flex items-center space-x-2 text-xs font-medium text-slate-500 sm:text-sm">
                <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-yellow-500 focus:ring-yellow-400">
                <span>Remember me</span>
            </label>
            <a href='{{ route('password.request') }}' wire:navigate class='text-xs font-bold text-yellow-700 hover:text-yellow-800 sm:text-sm'>Forgot password?</a>
        </div>

        <button type="submit"
            class="group flex w-full items-center justify-center gap-2 rounded-xl bg-darken py-3.5 font-bold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-slate-800">
            <span>Log in to Edlink</span><span class="transition-transform group-hover:translate-x-1">→</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        No account yet?
        <a href="{{ route('register') }}" wire:navigate class="font-bold text-yellow-600 hover:text-yellow-700">Start a free demo</a>
    </p>
</div>
