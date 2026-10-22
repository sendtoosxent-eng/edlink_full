<?php

namespace App\Models;

use App\Services\AccountingSetupService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_number', 'school_group_id', 'branch_name',
        'name', 'school_type',
        'slug', 'subdomain',
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

    public function badgeUrl(): ?string
    {
        return $this->badge_path && Storage::disk('public')->exists($this->badge_path)
            ? URL::signedRoute('profile-photo.show', ['type' => 'school', 'person' => $this->id, 'v' => substr(sha1($this->badge_path), 0, 12)])
            : null;
    }

    protected static function booted(): void
    {
        static::creating(function (School $school) {
            if (empty($school->school_number)) {
                $school->school_number = static::generateUniqueSchoolNumber();
            }
        });
        static::created(function (School $school): void {
            if (Schema::hasTable('financial_accounts')) {
                FinancialAccount::ensureDefaults($school);
            }
            if (Schema::hasTable('ledger_accounts')) {
                app(AccountingSetupService::class)->activate($school);
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

    public function smsConfiguration(): HasOne
    {
        return $this->hasOne(SchoolSmsConfiguration::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SchoolGroup::class, 'school_group_id');
    }

    public function accessibleUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_user_access')
            ->withPivot(['role', 'designation_id', 'can_view_group'])->withTimestamps();
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
        $reviewTerm = $this->reviewTermFor(auth()->user());
        if ($reviewTerm) {
            return $reviewTerm;
        }

        return Term::currentFor($this);
    }

    public function reviewTermFor(?User $user = null): ?Term
    {
        $user ??= auth()->user();
        if (! $user || $user->school_id !== $this->id || ! $this->canReviewClosedTerms($user)) {
            return null;
        }

        $termId = SchoolSetting::getValue($this->id, 'term_review_id');
        if (! $termId) {
            return null;
        }

        return $this->terms()->whereKey((int) $termId)->where('status', 'closed')->first();
    }

    public function canReviewClosedTerms(?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user || $user->school_id !== $this->id) {
            return false;
        }

        return ! in_array($user->role, ['student', 'parent'], true);
    }

    public function isReviewModeFor(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $this->reviewTermFor($user);
    }

    public function applyDefaultGraduatingClasses(): void
    {
        if ($this->classes()->where('is_graduating_class', true)->exists()) {
            return;
        }

        $types = match ($this->school_type) {
            'primary' => ['primary'],
            'secondary' => ['secondary'],
            'combined' => ['primary', 'secondary'],
            default => [],
        };
        if ($types === []) {
            return;
        }

        $this->classes()->get()->each(function (SchoolClass $class) use ($types): void {
            $name = strtolower(preg_replace('/[^a-z0-9]+/', ' ', $class->name));
            $isPrimarySeven = in_array('primary', $types, true) && (str_contains($name, 'primary seven') || preg_match('/\bp7\b|\bseven\b/', $name));
            $isS4 = in_array('secondary', $types, true) && (preg_match('/\bs ?4\b|\bsenior four\b/', $name));
            $isS6 = in_array('secondary', $types, true) && (preg_match('/\bs ?6\b|\bsenior six\b/', $name));
            if ($isPrimarySeven || $isS4 || $isS6) {
                $class->update(['is_graduating_class' => true]);
            }
        });
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
