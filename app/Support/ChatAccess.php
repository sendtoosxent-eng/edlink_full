<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ChatAccess
{
    public static function actor(): User
    {
        $user = auth()->user()?->fresh();
        abort_unless($user && $user->hasVerifiedEmail(), 403);
        $activeSchoolId = session('active_school_id');
        if ($activeSchoolId) {
            $school = $user->schoolAccesses()->whereKey($activeSchoolId)->first();
            abort_unless($school, 403);
            $user->school_id = $school->id;
            $user->role = $school->pivot->role;
        }
        abort_unless($user->school_id && $user->canLogin() && $user->school?->isLicenceUsable() && ! $user->school->isExpiredDemo(), 403);

        return $user;
    }

    public static function contacts(User $user): Builder
    {
        return User::query()->where(fn ($q) => $q->where('school_id', $user->school_id)
            ->orWhereHas('schoolAccesses', fn ($schools) => $schools->where('schools.id', $user->school_id)))
            ->whereKeyNot($user->id)
            ->whereNotNull('email_verified_at')
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhere('employment_status', '!=', 'inactive'))
            ->where(function ($q) use ($user) {
                $q->whereNotIn('role', ['parent', 'student'])
                    ->orWhereHas('portalStudents', fn ($students) => $students->where('students.status', 'active')->where('students.school_id', $user->school_id));
            });
    }
}
