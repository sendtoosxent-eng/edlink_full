<?php

namespace App\Models;

use App\Notifications\QueuedVerifyEmail;
use App\Notifications\QueuedResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            if ($user->school_id && Schema::hasTable('school_user_access')) {
                $user->schoolAccesses()->syncWithoutDetaching([$user->school_id => [
                    'role' => $user->role ?: 'admin', 'designation_id' => $user->designation_id, 'can_view_group' => false,
                ]]);
            }
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'school_id', 'designation_id',
        'role',
        'avatar_path',
        'staff_number', 'phone', 'job_title', 'base_salary', 'employment_status', 'joined_at',
        'emergency_contact_name', 'emergency_contact_phone', 'national_id', 'contract_type',
        'probation_ends_at', 'bank_name', 'bank_account_name', 'bank_account_number',
        'staff_document_path', 'staff_document_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'base_salary' => 'decimal:2',
            'joined_at' => 'date',
            'probation_ends_at' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolAccesses(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user_access')
            ->withPivot(['role', 'designation_id', 'can_view_group'])->withTimestamps();
    }

    public function canViewGroupDashboard(): bool
    {
        if (! $this->school?->school_group_id) return false;
        return $this->schoolAccesses()->where('school_group_id', $this->school->school_group_id)
            ->wherePivot('can_view_group', true)->exists();
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function hasModuleAccess(string $module): bool
    {
        if ($this->isSuperadmin() || $this->role === 'admin') return true;
        if (in_array($this->role, ['student', 'parent'], true)) return true;
        if (! $this->designation_id) return false;

        return collect($this->designation?->permissions ?? [])
            ->contains(fn (string $permission) => $permission === $module || str_starts_with($permission, $module.'.'));
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperadmin() || $this->role === 'admin') return true;
        if (! $this->designation_id) return false;

        $permissions = collect($this->designation?->permissions ?? []);
        if ($permissions->contains($permission)) return true;

        $module = str($permission)->before('.')->toString();
        $hasGranularRights = $permissions->contains(fn (string $item) => str_starts_with($item, $module.'.'));

        return ! $hasGranularRights && $permissions->contains($module);
    }

    public function portalHomeRoute(): string
    {
        if (in_array($this->role, ['parent', 'student'], true)) return 'portal.home';
        return ($this->designation_id || in_array($this->role, ['teacher', 'bursar'], true)) ? 'workbench.home' : 'dashboard';
    }

    public function portalStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'portal_user_students')
            ->withPivot('school_id', 'relationship')
            ->withTimestamps();
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Public URL for the user's avatar, or null if they haven't uploaded one
     * (the UI falls back to their initial letter in that case).
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function generateOtp(): string
    {
        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        return $code;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    public function otpIsValid(string $code): bool
    {
        return $this->otp_code
            && hash_equals($this->otp_code, $code)
            && $this->otp_expires_at
            && $this->otp_expires_at->isFuture();
    }

    public function clearOtp(): void
    {
        $this->forceFill([
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();
    }
}
