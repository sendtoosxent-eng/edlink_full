<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_number',
        'name', 'school_type',
        'slug',
        'status',
        'is_demo',
        'demo_expires_at',
        'license_expires_at',
        'license_plan', 'license_status', 'license_started_at', 'license_student_limit',
        'email', 'phone', 'address',
        'badge_path', 'motto', 'website', 'principal_name',
    ];

    protected $casts = [
        'is_demo' => 'boolean',
        'demo_expires_at' => 'datetime',
        'license_expires_at' => 'datetime',
        'license_started_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (School $school) {
            if (empty($school->school_number)) {
                $school->school_number = static::generateUniqueSchoolNumber();
            }
        });
    }

    protected static function generateUniqueSchoolNumber(): string
    {
        do {
            $number = 'EDL-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));
        } while (static::where('school_number', $number)->exists());

        return $number;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(StudentEnrolment::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function studentCategories(): HasMany
    {
        return $this->hasMany(StudentCategory::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function currentTerm(): ?Term
    {
        return Term::currentFor($this);
    }

    public function activeStudentCount(): int
    {
        return (int) $this->students()->where('status', 'active')->count();
    }

    public function hasStudentCapacity(): bool
    {
        return $this->license_student_limit === null || $this->activeStudentCount() < $this->license_student_limit;
    }

    public function isLicenceUsable(): bool
    {
        return in_array($this->license_status, ['active', 'trial'], true)
            && (! $this->license_expires_at || $this->license_expires_at->isFuture());
    }
    public function isExpiredDemo(): bool
    {
        return $this->is_demo && $this->demo_expires_at && $this->demo_expires_at->isPast();
    }
}
