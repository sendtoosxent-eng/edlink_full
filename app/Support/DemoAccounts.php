<?php

namespace App\Support;

final class DemoAccounts
{
    public const SEED_VERSION = '6';

    public const SCHOOL_NUMBER = 'EDL-TEACH';

    public const PASSWORD = 'TeacherTest@2026';

    public const SCHOOL_TYPES = [
        'kindergarten' => [
            'label' => 'Kindergarten',
            'description' => 'Play-based early learning, nursery classes and child progress.',
            'school_number' => 'EDL-KINDER',
            'icon' => '🧸',
        ],
        'primary' => [
            'label' => 'Primary School',
            'description' => 'Primary 1–7, class teachers, continuous assessment and PLE preparation.',
            'school_number' => self::SCHOOL_NUMBER,
            'icon' => '📚',
        ],
        'secondary' => [
            'label' => 'Secondary School',
            'description' => 'Senior 1–6, subject teachers, O-Level and A-Level academics.',
            'school_number' => 'EDL-SECOND',
            'icon' => '🎓',
        ],
        'vocational' => [
            'label' => 'Vocational Institute',
            'description' => 'Certificate and diploma cohorts, practical units and trade assessment.',
            'school_number' => 'EDL-VOCAT',
            'icon' => '🛠️',
        ],
    ];

    public const ROLES = [
        'administrator' => ['label' => 'School Administrator', 'email' => 'admin@edlink.local', 'description' => 'Explore school setup, staff, academics, finance and reports.'],
        'class-teacher' => ['label' => 'Class Teacher', 'email' => 'class.teacher@edlink.local', 'description' => 'View every subject and learner in the assigned class.'],
        'subject-teacher' => ['label' => 'Subject Teacher', 'email' => 'subject.teacher@edlink.local', 'description' => 'Work only with explicitly assigned subjects and classes.'],
        'bursar' => ['label' => 'Bursar', 'email' => 'bursar@edlink.local', 'description' => 'Explore fee collection, expenses, payroll and finance reports.'],
        'parent' => ['label' => 'Parent', 'email' => 'parent@edlink.local', 'description' => 'Follow a linked learner’s results, attendance and homework.'],
        'student' => ['label' => 'Student', 'email' => 'student@edlink.local', 'description' => 'Open the learner portal for results, homework and events.'],
    ];

    public static function roles(): array
    {
        return config('edlink.demo.roles', self::ROLES) ?: self::ROLES;
    }

    public static function role(string $key): ?array
    {
        return self::roles()[$key] ?? null;
    }

    public static function schoolTypes(): array
    {
        return config('edlink.demo.school_types', self::SCHOOL_TYPES) ?: self::SCHOOL_TYPES;
    }

    public static function schoolType(string $key): ?array
    {
        return self::schoolTypes()[$key] ?? null;
    }

    public static function schoolNumber(?string $type = null): string
    {
        if ($type && ($schoolType = self::schoolType($type))) {
            return (string) $schoolType['school_number'];
        }

        return (string) config('edlink.demo.school_number', self::SCHOOL_NUMBER);
    }

    public static function password(): string
    {
        return (string) config('edlink.demo.password', self::PASSWORD);
    }

    public static function includes(string $schoolNumber, string $email): bool
    {
        $demoNumbers = collect(self::schoolTypes())->pluck('school_number');
        if (! $demoNumbers->contains(fn (string $number): bool => strcasecmp(trim($schoolNumber), $number) === 0)) {
            return false;
        }

        return collect(self::roles())->contains(
            fn (array $account): bool => strcasecmp(trim((string) ($account['email'] ?? '')), trim($email)) === 0
        );
    }
}
