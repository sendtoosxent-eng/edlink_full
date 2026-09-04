<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\LoginRequest;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use App\Support\DemoAccounts;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function school(Request $request)
    {
        $data = $request->validate(['school_number' => ['required', 'string', 'max:32']]);
        $school = School::where('school_number', strtoupper(trim($data['school_number'])))->first();

        if (! $school || ! $school->isLicenceUsable() || $school->isExpiredDemo()) {
            throw ValidationException::withMessages(['school_number' => ['We could not find an active school with that number.']]);
        }

        return $this->ok([
            'number' => $school->school_number,
            'name' => $school->name,
            'logo_url' => $school->badgeUrl(),
            'is_demo' => (bool) $school->is_demo,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $school = School::where('school_number', strtoupper($request->string('school_number')))->first();
        $user = $school?->users()->whereRaw('LOWER(email) = ?', [strtolower($request->string('email'))])->first();

        if (! $school || ! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => ['The school number, email, or password is incorrect.']]);
        }
        abort_if($user->employment_status === 'inactive', 403, 'This staff account is inactive.');
        abort_unless($user->hasVerifiedEmail(), 403, 'Verify your email address before using the mobile application.');
        abort_unless(in_array($user->role, ['teacher', 'student', 'parent'], true), 403, 'This account is not enabled for the mobile application.');
        abort_unless($school->isLicenceUsable() && ! $school->isExpiredDemo(), 403, 'This school is not currently active.');

        if ($request->filled('expected_role') && $user->role !== $request->string('expected_role')->toString()) {
            throw ValidationException::withMessages(['email' => ['These credentials do not match the selected account role.']]);
        }

        $otpEnabled = SchoolSetting::where(['school_id' => $school->id, 'key' => 'otp_enabled'])->value('value') === 'enabled';
        $isSuspicious = $otpEnabled || ($user->last_login_ip !== null && $user->last_login_ip !== $request->ip()) || config('app.otp_force');
        $isDemoAccount = DemoAccounts::includes($school->school_number, $user->email);

        if ($isSuspicious && ! $isDemoAccount) {
            $code = $user->generateOtp();
            $user->notify((new OtpCodeNotification($code))->onConnection('sync'));

            return $this->ok([
                'otp_required' => true,
                'challenge_token' => $this->otpChallenge($user, $request->string('device_name')->toString()),
                'masked_email' => $this->maskEmail($user->email),
                'expires_in' => 600,
            ]);
        }

        return $this->completeLogin($request, $user);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);
        [$user, $challenge] = $this->resolveOtpChallenge($data['challenge_token']);
        $key = 'mobile-otp-verify|'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => ['Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']]);
        }
        if (! $user->otpIsValid($data['code'])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['code' => ['That code is invalid or has expired.']]);
        }

        RateLimiter::clear($key);
        $user->clearOtp();
        return $this->completeLogin($request, $user, $challenge['device_name'] ?? $data['device_name']);
    }

    public function resendOtp(Request $request)
    {
        $data = $request->validate(['challenge_token' => ['required', 'string']]);
        [$user] = $this->resolveOtpChallenge($data['challenge_token']);
        $key = 'mobile-otp-resend|'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw ValidationException::withMessages(['code' => ['Please wait '.RateLimiter::availableIn($key).' seconds before requesting another code.']]);
        }
        RateLimiter::hit($key, 60);
        $user->notify((new OtpCodeNotification($user->generateOtp()))->onConnection('sync'));

        return $this->ok(['sent' => true, 'masked_email' => $this->maskEmail($user->email)]);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'school_number' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255'],
        ]);
        $school = School::where('school_number', strtoupper(trim($data['school_number'])))->first();
        if ($school) {
            Password::broker()->sendResetLink(['school_id' => $school->id, 'email' => strtolower(trim($data['email']))]);
        }

        return $this->ok(['message' => 'If those details match an Edlink account, a password reset link has been sent by email.']);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'school_number' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);
        $school = School::where('school_number', strtoupper(trim($data['school_number'])))->first();
        if (! $school) {
            throw ValidationException::withMessages(['email' => ['This password reset link is invalid or has expired.']]);
        }
        $status = Password::broker()->reset([
            'email' => strtolower(trim($data['email'])), 'school_id' => $school->id,
            'password' => $data['password'], 'password_confirmation' => $data['password_confirmation'], 'token' => $data['token'],
        ], function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            $user->clearOtp();
            if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => ['This password reset link is invalid or has expired.']]);
        }

        return $this->ok(['reset' => true]);
    }

    private function completeLogin(Request $request, User $user, ?string $deviceName = null)
    {
        $school = $user->school;
        abort_unless($school && $school->isLicenceUsable() && ! $school->isExpiredDemo(), 403, 'This school is not currently active.');

        $token = $user->createToken(
            $deviceName ?: $request->string('device_name'),
            ['mobile'],
            now()->addMinutes((int) config('sanctum.expiration', 10080)),
        )->plainTextToken;
        $user->forceFill(['last_login_ip' => $request->ip()])->save();
        AuditLog::record($school->id, 'mobile.login', $user, ['device_name' => $deviceName ?: $request->string('device_name')]);

        return $this->ok(['otp_required' => false, 'token' => $token, 'user' => $this->userPayload($user)]);
    }

    public function me(Request $request) { return $this->ok($this->userPayload($request->user())); }

    public function logout(Request $request)
    {
        AuditLog::record($request->user()->school_id, 'mobile.logout', $request->user());
        $request->user()->currentAccessToken()?->delete();
        return $this->ok(['logged_out' => true]);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('school', 'designation');
        return [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'avatar_url' => $user->avatarUrl(),
            'school' => ['id' => $user->school->id, 'number' => $user->school->school_number, 'name' => $user->school->name],
            'permissions' => array_values($user->designation?->permissions ?? []),
        ];
    }

    private function otpChallenge(User $user, string $deviceName): string
    {
        return Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'school_id' => $user->school_id,
            'device_name' => $deviceName,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    private function resolveOtpChallenge(string $token): array
    {
        try {
            $challenge = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw ValidationException::withMessages(['code' => ['This verification request is invalid or has expired.']]);
        }
        if (($challenge['expires_at'] ?? 0) < now()->timestamp) {
            throw ValidationException::withMessages(['code' => ['This verification request has expired. Please sign in again.']]);
        }
        $user = User::with('school')->whereKey($challenge['user_id'] ?? 0)->where('school_id', $challenge['school_id'] ?? 0)->first();
        if (! $user || $user->employment_status === 'inactive' || ! $user->school?->isLicenceUsable() || $user->school->isExpiredDemo()) {
            throw ValidationException::withMessages(['code' => ['This verification request is no longer valid.']]);
        }
        return [$user, $challenge];
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return mb_substr($name, 0, 2).str_repeat('•', max(2, mb_strlen($name) - 2)).'@'.$domain;
    }
}
