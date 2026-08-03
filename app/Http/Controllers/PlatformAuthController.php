<?php

namespace App\Http\Controllers;

use App\Models\PlatformAdmin;
use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\PlatformTotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PlatformAuthController extends Controller
{
    public function __construct(private readonly PlatformTotpService $totp) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::guard('platform')->check()) return $this->nextStep();
        return view('platform.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email'=>['required','email','max:255'],'password'=>['required','string','max:255']]);
        $key = 'platform-login|'.strtolower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) return back()->withErrors(['email'=>'Too many sign-in attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('email');
        if (! Auth::guard('platform')->attempt(['email'=>$credentials['email'],'password'=>$credentials['password'],'is_active'=>true])) {
            RateLimiter::hit($key, 60); $this->audit($request, 'platform.login.failed', PlatformAdmin::where('email',$credentials['email'])->value('id'));
            return back()->withErrors(['email'=>'The supplied credentials could not be verified.'])->onlyInput('email');
        }
        RateLimiter::clear($key); $request->session()->regenerate();
        $request->session()->forget(['platform_mfa_passed','platform_last_activity']);
        $this->audit($request, 'platform.password.verified', Auth::guard('platform')->id());
        return $this->nextStep();
    }

    public function showSetup(): View|RedirectResponse
    {
        $admin = Auth::guard('platform')->user();
        if ($admin->totp_confirmed_at) return redirect()->route('platform.challenge');
        if (! $admin->totp_secret) $admin->update(['totp_secret'=>$this->totp->generateSecret()]);
        return view('platform.auth.setup', ['admin'=>$admin->fresh(),'qrCode'=>$this->totp->qrDataUri($admin->email,$admin->fresh()->totp_secret)]);
    }

    public function confirmSetup(Request $request): View|RedirectResponse
    {
        $data=$request->validate(['code'=>['required','digits:6']]); $admin=Auth::guard('platform')->user();
        $key='platform-totp-setup|'.$admin->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key,5)) return back()->withErrors(['code'=>'Too many incorrect codes. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        if (!$this->totp->verify($admin->totp_secret,$data['code'])) { RateLimiter::hit($key,60); $this->audit($request,'platform.totp.setup_failed',$admin->id); return back()->withErrors(['code'=>'That authenticator code is not valid.']); }
        RateLimiter::clear($key); $plain=$this->totp->recoveryCodes();
        $admin->update(['totp_confirmed_at'=>now(),'recovery_codes'=>collect($plain)->map(fn($code)=>Hash::make($code))->all(),'last_totp_hash'=>null]);
        $request->session()->regenerate(); $request->session()->put(['platform_mfa_passed'=>true,'platform_last_activity'=>now()->timestamp]);
        $this->audit($request,'platform.totp.enabled',$admin->id);
        return view('platform.auth.recovery-codes',['codes'=>$plain]);
    }

    public function showChallenge(): View|RedirectResponse
    {
        $admin=Auth::guard('platform')->user();
        if (!$admin->totp_confirmed_at) return redirect()->route('platform.setup');
        if (session('platform_mfa_passed')) return redirect()->route('platform.dashboard');
        return view('platform.auth.challenge');
    }

    public function challenge(Request $request): RedirectResponse
    {
        $data=$request->validate(['code'=>['required','string','max:32']]); $admin=Auth::guard('platform')->user();
        $key='platform-mfa|'.$admin->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key,5)) return back()->withErrors(['code'=>'Too many incorrect codes. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        $code=strtoupper(trim($data['code'])); $valid=false; $method='totp';
        if (preg_match('/^\d{6}$/',$code) && $this->totp->verify($admin->totp_secret,$code) && !hash_equals((string)$admin->last_totp_hash,$this->codeHash($code))) {
            $valid=true; $admin->update(['last_totp_hash'=>$this->codeHash($code)]);
        } else {
            foreach ($admin->recovery_codes ?? [] as $index=>$hash) if (Hash::check($code,$hash)) { $codes=$admin->recovery_codes; unset($codes[$index]); $admin->update(['recovery_codes'=>array_values($codes)]); $valid=true; $method='recovery_code'; break; }
        }
        if (!$valid) { RateLimiter::hit($key,60); $this->audit($request,'platform.mfa.failed',$admin->id); return back()->withErrors(['code'=>'The security code could not be verified.']); }
        RateLimiter::clear($key); $request->session()->regenerate(); $request->session()->put(['platform_mfa_passed'=>true,'platform_last_activity'=>now()->timestamp]);
        $admin->update(['last_login_at'=>now(),'last_login_ip'=>$request->ip()]); $this->audit($request,'platform.login.succeeded',$admin->id,['method'=>$method]);
        return redirect()->intended(route('platform.dashboard'));
    }

    public function showMfaReset(): View
    {
        return view('platform.auth.reset-mfa');
    }

    public function resetMfa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'max:255'],
            'confirmation' => ['required', 'in:RESET MFA'],
        ], [
            'confirmation.in' => 'Type RESET MFA exactly to confirm.',
        ]);
        $admin = Auth::guard('platform')->user();
        $key = 'platform-mfa-reset|'.$admin->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['password' => 'Too many reset attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (! Hash::check($data['password'], $admin->password)) {
            RateLimiter::hit($key, 60);
            $this->audit($request, 'platform.mfa.reset_failed', $admin->id);
            return back()->withErrors(['password' => 'The platform password is incorrect.']);
        }

        RateLimiter::clear($key);
        $admin->update([
            'totp_secret' => null,
            'totp_confirmed_at' => null,
            'recovery_codes' => [],
            'last_totp_hash' => null,
        ]);
        $request->session()->forget(['platform_mfa_passed', 'platform_last_activity']);
        $request->session()->regenerate();
        $this->audit($request, 'platform.mfa.reset', $admin->id);

        return redirect()->route('platform.setup')->with('status', 'MFA was reset. Connect your authenticator again to continue.');
    }

    public function dashboard(): View
    {
        $schools = School::withCount(['students','users'])->latest()->get();
        $activeSchools = $schools->filter(fn ($school) => $school->license_status === 'active' && (! $school->license_expires_at || $school->license_expires_at->isFuture()));
        $expiring = $schools->filter(fn ($school) => $school->license_expires_at && $school->license_expires_at->isFuture() && $school->license_expires_at->lte(now()->addDays(30)))->sortBy('license_expires_at');
        $expired = $schools->filter(fn ($school) => $school->license_status === 'expired' || ($school->license_expires_at && $school->license_expires_at->isPast()));
        $months = collect(range(5, 0))->map(fn ($offset) => now()->subMonths($offset)->startOfMonth());
        $registrations = $months->map(fn ($month) => $schools->filter(fn ($school) => $school->created_at?->isSameMonth($month))->count());
        $subscriptionSeries = collect(['basic', 'premium', 'enterprise'])->mapWithKeys(fn ($plan) => [
            $plan => $months->map(fn ($month) => $schools->filter(fn ($school) =>
                $school->license_plan === $plan && $school->created_at?->lte($month->copy()->endOfMonth())
            )->count())->values(),
        ]);
        $platformLogs = PlatformAuditLog::with('administrator:id,name')->latest()->limit(7)->get();

        return view('platform.dashboard', [
            'admin' => Auth::guard('platform')->user(), 'schools' => $schools, 'activeSchools' => $activeSchools,
            'trialSchools' => $schools->where('license_status','trial'), 'expiredSchools' => $expired, 'expiringSchools' => $expiring,
            'totalStudents' => Student::count(), 'totalStaff' => User::whereNotIn('role',['student','parent'])->count(),
            'recentSchools' => $schools->take(6), 'platformLogs' => $platformLogs,
            'planCounts' => $schools->groupBy('license_plan')->map->count(),
            'registrationLabels' => $months->map(fn ($month) => $month->format('M'))->values(),
            'registrationData' => $registrations->values(),
            'subscriptionSeries' => $subscriptionSeries,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->audit($request,'platform.logout',Auth::guard('platform')->id()); Auth::guard('platform')->logout();
        $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('platform.login');
    }

    private function nextStep(): RedirectResponse
    {
        return redirect()->route(Auth::guard('platform')->user()->totp_confirmed_at ? 'platform.challenge' : 'platform.setup');
    }

    private function codeHash(string $code): string { return hash_hmac('sha256',$code,(string)config('app.key')); }

    private function audit(Request $request,string $event,?int $adminId,array $metadata=[]): void
    {
        PlatformAuditLog::create(['platform_admin_id'=>$adminId,'event'=>$event,'metadata'=>$metadata ?: null,'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent() ?? '')->limit(500)->toString() ?: null]);
    }
}
