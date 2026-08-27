<?php

namespace App\Support;

class DesignationPermissions
{
    public static function groups(): array
    {
        return [
            'students' => ['label' => 'Students & admissions', 'rights' => ['students.view' => 'View learners', 'students.manage' => 'Register and manage learners', 'students.activities' => 'Manage houses and clubs']],
            'finance' => ['label' => 'Finance', 'rights' => ['finance.payments' => 'Record payments and receipts', 'finance.adjustments' => 'Request and review individual fee adjustments', 'finance.expenses' => 'Manage expenses', 'finance.ledger' => 'Manage ledger, reversals and reconciliation', 'finance.reports' => 'View financial reports']],
            'accounting' => ['label' => 'Accounting & controls', 'rights' => [
                'accounting.dashboard.view' => 'View accounting dashboard', 'accounting.accounts.view' => 'View chart of accounts', 'accounting.accounts.manage' => 'Manage chart of accounts',
                'accounting.mappings.manage' => 'Manage posting rules', 'accounting.journals.create' => 'Create manual journals', 'accounting.journals.submit' => 'Submit journals',
                'accounting.journals.approve' => 'Approve journals', 'accounting.journals.post' => 'Post journals', 'accounting.journals.reverse' => 'Reverse journals',
                'accounting.ledger.view' => 'View general ledger', 'accounting.reports.view' => 'View financial reports', 'accounting.reports.export' => 'Export financial reports',
                'accounting.assets.view' => 'View fixed asset register', 'accounting.assets.manage' => 'Register assets and categories', 'accounting.assets.depreciate' => 'Prepare depreciation', 'accounting.assets.approve' => 'Approve asset postings',
                'accounting.periods.manage' => 'Manage accounting periods', 'accounting.periods.reopen' => 'Reopen accounting periods', 'accounting.budgets.manage' => 'Manage budgets',
                'accounting.reconciliations.manage' => 'Reconcile bank accounts', 'accounting.opening_balances.manage' => 'Manage opening balances',
            ]],
            'attendance' => ['label' => 'Attendance', 'rights' => ['attendance.daily' => 'Mark daily learner attendance', 'attendance.subject' => 'Mark subject attendance', 'attendance.reports' => 'View attendance reports']],
            'academics' => ['label' => 'Academics', 'rights' => ['academics.classes' => 'Manage classes and streams', 'academics.subjects' => 'Manage subjects and allocations', 'academics.timetable' => 'Manage timetable', 'academics.events' => 'Manage events', 'academics.promotions' => 'Run promotions']],
            'exams' => ['label' => 'Exams & results', 'rights' => ['exams.setup' => 'Create and configure exams', 'exams.marks' => 'Enter and submit marks', 'exams.results' => 'View and approve results']],
            'staff' => ['label' => 'Staff', 'rights' => ['staff.directory' => 'View staff directory', 'staff.manage' => 'Register and manage staff', 'staff.attendance' => 'Mark staff attendance', 'staff.payroll' => 'Run payroll', 'staff.leaves' => 'Manage leave requests', 'staff.designations' => 'Manage designations and access']],
            'parents' => ['label' => 'Parents & communication', 'rights' => ['parents.manage' => 'Manage parent accounts', 'parents.communications' => 'Send communications']],
            'reports' => ['label' => 'Reports', 'rights' => ['reports.view' => 'View, print and export reports']],
            'settings' => ['label' => 'System settings', 'rights' => ['settings.manage' => 'Manage school and system settings']],
        ];
    }

    public static function defaults(): array
    {
        return [
            'Class Teacher' => ['attendance.daily', 'attendance.reports', 'academics.events', 'exams.marks', 'exams.results', 'parents.communications', 'reports.view'],
            'Subject Teacher' => ['attendance.subject', 'academics.subjects', 'academics.timetable', 'exams.marks', 'exams.results', 'reports.view'],
            'Bursar' => ['students.view', 'finance.payments', 'finance.adjustments', 'finance.expenses', 'finance.ledger', 'finance.reports', 'staff.payroll', 'reports.view', 'accounting.dashboard.view', 'accounting.accounts.view', 'accounting.journals.create', 'accounting.journals.submit', 'accounting.ledger.view', 'accounting.reports.view', 'accounting.assets.view', 'accounting.assets.manage', 'accounting.assets.depreciate', 'accounting.reconciliations.manage'],
            'DOS' => ['students.view', 'students.manage', 'attendance.daily', 'attendance.subject', 'attendance.reports', 'academics.classes', 'academics.subjects', 'academics.timetable', 'academics.events', 'academics.promotions', 'exams.setup', 'exams.marks', 'exams.results', 'staff.directory', 'staff.attendance', 'reports.view'],
        ];
    }
}
