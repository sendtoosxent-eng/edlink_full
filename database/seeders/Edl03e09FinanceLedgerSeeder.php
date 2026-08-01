<?php

namespace Database\Seeders;

use App\Models\FinanceLedgerEntry;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class Edl03e09FinanceLedgerSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('school_number', 'EDL-03E09')->firstOrFail();
        $term = $school->currentTerm();
        $recorder = User::where('school_id', $school->id)->where('role', 'bursar')->firstOrFail();
        $administrator = User::where('school_id', $school->id)->where('role', 'admin')->firstOrFail();

        $entries = [
            [
                'reference' => 'TEST-EDL03E09-PEND-001',
                'entry_type' => 'expense',
                'direction' => 'debit',
                'amount' => 1500000,
                'description' => '[TEST DATA] Classroom furniture purchase awaiting approval',
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
            ],
            [
                'reference' => 'TEST-EDL03E09-PEND-002',
                'entry_type' => 'payroll',
                'direction' => 'debit',
                'amount' => 2400000,
                'description' => '[TEST DATA] Staff payroll batch awaiting approval',
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
            ],
            [
                'reference' => 'TEST-EDL03E09-POST-001',
                'entry_type' => 'fee_payment',
                'direction' => 'credit',
                'amount' => 3000000,
                'description' => '[TEST DATA] Posted tuition collection for reversal testing',
                'status' => 'posted',
                'approved_by' => $administrator->id,
                'approved_at' => now(),
            ],
            [
                'reference' => 'TEST-EDL03E09-POST-002',
                'entry_type' => 'expense',
                'direction' => 'debit',
                'amount' => 250000,
                'description' => '[TEST DATA] Posted utilities expense for reversal testing',
                'status' => 'posted',
                'approved_by' => $administrator->id,
                'approved_at' => now(),
            ],
        ];

        foreach ($entries as $entry) {
            FinanceLedgerEntry::updateOrCreate(
                ['reference' => $entry['reference']],
                $entry + [
                    'school_id' => $school->id,
                    'term_id' => $term?->id,
                    'source_type' => 'test_seed',
                    'source_id' => array_search($entry, $entries, true) + 1,
                    'recorded_by' => $recorder->id,
                    'posted_at' => now(),
                    'reversal_of_id' => null,
                    'reversal_reason' => null,
                ]
            );
        }

        $this->command?->info('Seeded finance approval, reversal, and reconciliation test entries for EDL-03E09.');
    }
}
