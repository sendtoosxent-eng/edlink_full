<?php

namespace App\Support;

final class DemoAccounts
{
    public const SCHOOL_NUMBER = 'EDL-TEACH';

    public const PASSWORD = 'TeacherTest@2026';

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

    public static function schoolNumber(): string
    {
        return (string) config('edlink.demo.school_number', self::SCHOOL_NUMBER);
    }

    public static function password(): string
    {
        return (string) config('edlink.demo.password', self::PASSWORD);
    }
}
