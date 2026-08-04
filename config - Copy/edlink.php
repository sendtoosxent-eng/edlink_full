<?php
return [
    'finance_approval_threshold' => (float) env('FINANCE_APPROVAL_THRESHOLD', 1000000),
    'backup_retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'privacy_retention_days' => (int) env('PRIVACY_RETENTION_DAYS', 2555),
];
