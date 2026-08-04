<?php

namespace Database\Seeders;

use App\Models\CashPoolEntry;
use App\Models\Designation;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\User;
use App\Support\DesignationPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BursarDashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('is_demo', true)->latest('id')->firstOrFail();

        if ($school->students()->where('status', 'active')->count() < 3) {
            $this->call(ReportingDemoSeeder::class);
        }
        $school->refresh();
        $term = $school->currentTerm();

        $designation = Designation::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Bursar'],
            ['description' => 'Finance, payments, expenses, payroll and financial reporting.', 'permissions' => DesignationPermissions::defaults()['Bursar']],
        );

        $bursar = User::updateOrCreate(
            ['school_id' => $school->id, 'email' => 'bursar@demo.edlink.test'],
            [
                'designation_id' => $designation->id,
                'staff_number' => 'STF-BURSAR-DEMO',
                'name' => 'Demo School Bursar',
                'phone' => '+256 700 555 010',
                'job_title' => 'School Bursar',
                'role' => 'bursar',
                'base_salary' => 1800000,
                'employment_status' => 'active',
                'joined_at' => now()->subYears(2)->toDateString(),
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
        $bursar->forceFill(['email_verified_at' => now()])->save();

        $students = $school->students()->where('status', 'active')->orderBy('id')->take(6)->get();
        $expenseTemplates = [
            ['Utilities', 'UMEME electricity and water', 420000],
            ['Supplies', 'Classroom and office supplies', 285000],
            ['Maintenance', 'Buildings and equipment maintenance', 360000],
            ['Transport', 'School transport fuel and servicing', 510000],
            ['Meals', 'Staff and learner meal supplies', 640000],
            ['Other', 'Administrative operating costs', 225000],
        ];

        DB::transaction(function () use ($school, $term, $bursar, $students, $expenseTemplates) {
            foreach (range(5, 0) as $monthIndex => $offset) {
                $month = now()->subMonths($offset)->startOfMonth();
                $monthCode = $month->format('Ym');

                foreach ($students->take(3) as $studentIndex => $student) {
                    $amount = 180000 + (($monthIndex + 1) * 35000) + ($studentIndex * 45000);
                    $payment = FeePayment::updateOrCreate(
                        [
                            'school_id' => $school->id,
                            'transaction_id' => 'BURSAR-DEMO-'.$monthCode.'-'.($studentIndex + 1),
                        ],
                        [
                            'student_id' => $student->id,
                            'term_id' => $term->id,
                            'amount' => $amount,
                            'method' => ['cash', 'mobile_money', 'bank'][$studentIndex],
                            'bank_slip_number' => $studentIndex === 2 ? 'BNK-'.$monthCode.'-03' : null,
                            'notes' => 'Bursar dashboard demo collection',
                            'recorded_by' => $bursar->id,
                            'paid_at' => $month->copy()->addDays(4 + ($studentIndex * 6)),
                        ],
                    );

                    CashPoolEntry::updateOrCreate(
                        ['fee_payment_id' => $payment->id],
                        [
                            'school_id' => $school->id,
                            'term_id' => $term->id,
                            'direction' => 'credit',
                            'amount' => $payment->amount,
                            'description' => 'Fee payment: '.$student->name.' · '.$payment->transaction_id,
                            'transacted_at' => $payment->paid_at,
                            'recorded_by' => $bursar->id,
                        ],
                    );
                }

                foreach ([0, 1] as $expenseIndex) {
                    $template = $expenseTemplates[($monthIndex + $expenseIndex) % count($expenseTemplates)];
                    [$category, $description, $baseAmount] = $template;
                    $reference = 'PV-'.$monthCode.'-'.str_pad((string) ($expenseIndex + 1), 2, '0', STR_PAD_LEFT);
                    $expense = Expense::updateOrCreate(
                        ['school_id' => $school->id, 'reference_number' => $reference],
                        [
                            'term_id' => $term->id,
                            'category' => $category,
                            'amount' => $baseAmount + ($monthIndex * 25000) + ($expenseIndex * 40000),
                            'description' => $description,
                            'expense_date' => $month->copy()->addDays(10 + ($expenseIndex * 11)),
                            'recorded_by' => $bursar->id,
                        ],
                    );

                    CashPoolEntry::updateOrCreate(
                        ['expense_id' => $expense->id],
                        [
                            'school_id' => $school->id,
                            'term_id' => $term->id,
                            'direction' => 'debit',
                            'amount' => $expense->amount,
                            'description' => '['.$expense->reference_number.'] '.$expense->category.': '.$expense->description,
                            'transacted_at' => $expense->expense_date->startOfDay(),
                            'recorded_by' => $bursar->id,
                        ],
                    );
                }
            }
        });

        $this->command?->info("Bursar dashboard demo data seeded for {$school->name}.");
        $this->command?->info('Login: bursar@demo.edlink.test / password');
        $this->command?->info('Created 18 monthly payments, 12 referenced expenses, and matching cash-pool entries.');
    }
}