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
];
