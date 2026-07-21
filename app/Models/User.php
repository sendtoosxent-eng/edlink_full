<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'school_id', 'designation_id',
        'role',
        'avatar_path',
        'staff_number', 'phone', 'job_title', 'base_salary', 'employment_status', 'joined_at',
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
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function hasModuleAccess(string $module): bool
    {
        if ($this->isSuperadmin() || $this->role === 'admin') {
            return true;
        }

        if (in_array($this->role, ['student', 'parent'], true)) {
            return true;
        }

        if (! $this->designation_id) {
            return false;
        }

        return in_array($module, $this->designation?->permissions ?? [], true);
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
