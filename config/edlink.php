<?php

return [
    'finance_approval_threshold' => (float) env('FINANCE_APPROVAL_THRESHOLD', 1000000),
    'backup_retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'privacy_retention_days' => (int) env('PRIVACY_RETENTION_DAYS', 2555),
    'mail' => [
        'support_address' => env('EDLINK_SUPPORT_EMAIL', 'support@edlink.space'),
        'support_name' => env('EDLINK_SUPPORT_NAME', 'Edlink Support'),
    ],
    'backup' => [
        'mysql_dump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
    ],
    'demo' => [
        'school_number' => env('EDLINK_DEMO_SCHOOL_NUMBER', 'EDL-TEACH'),
        'password' => env('EDLINK_DEMO_PASSWORD', 'TeacherTest@2026'),
        'roles' => [
            'administrator' => ['label' => 'School Administrator', 'email' => 'admin@edlink.local', 'description' => 'Explore school setup, staff, academics, finance and reports.'],
            'class-teacher' => ['label' => 'Class Teacher', 'email' => 'class.teacher@edlink.local', 'description' => 'View every subject and learner in the assigned class.'],
            'subject-teacher' => ['label' => 'Subject Teacher', 'email' => 'subject.teacher@edlink.local', 'description' => 'Work only with explicitly assigned subjects and classes.'],
            'bursar' => ['label' => 'Bursar', 'email' => 'bursar@edlink.local', 'description' => 'Explore fee collection, expenses, payroll and finance reports.'],
            'parent' => ['label' => 'Parent', 'email' => 'parent@edlink.local', 'description' => 'Follow a linked learner’s results, attendance and homework.'],
            'student' => ['label' => 'Student', 'email' => 'student@edlink.local', 'description' => 'Open the learner portal for results, homework and events.'],
        ],
    ],
];
