<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\LoginRequest;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function login(LoginRequest $request)
    {
        $school = School::where('school_number', strtoupper($request->string('school_number')))->first();
        $user = $school?->users()->whereRaw('LOWER(email) = ?', [strtolower($request->string('email'))])->first();

        if (! $school || ! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => ['The school number, email, or password is incorrect.']]);
        }
        abort_if($user->employment_status === 'inactive', 403, 'This staff account is inactive.');
        abort_unless(in_array($user->role, ['teacher', 'student', 'parent'], true), 403, 'This account is not enabled for the mobile application.');
        abort_unless($school->isLicenceUsable() && ! $school->isExpiredDemo(), 403, 'This school is not currently active.');

        $token = $user->createToken($request->string('device_name'), ['mobile'])->plainTextToken;
        AuditLog::record($school->id, 'mobile.login', $user, ['device_name' => $request->string('device_name')]);

        return $this->ok(['token' => $token, 'user' => $this->userPayload($user)]);
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
}
