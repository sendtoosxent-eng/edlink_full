<?php

use App\Models\AccountingJournal;
use App\Models\AttendanceRecord;
use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('seeds the full demo range without duplicates, overwrites or outgoing receipts', function () {
    Mail::fake();
    $school = School::create(['name' => 'Autumn Demo', 'slug' => 'autumn-demo', 'school_number' => 'EDL-TEACH', 'is_demo' => true]);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 3', 'year' => 2026, 'is_current' => true, 'status' => 'open', 'locked' => false]);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 1']);
    $students = collect(range(1, 3))->map(fn ($i) => Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'Demo Learner '.$i, 'status' => 'active']));
    User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    User::factory()->create(['school_id' => $school->id, 'role' => 'bursar']);
    $existing = AttendanceRecord::create(['school_id' => $school->id, 'student_id' => $students->first()->id, 'term_id' => $term->id, 'attendance_date' => '2026-10-20', 'session_key' => 'daily', 'status' => 'excused', 'note' => 'Preserve this existing register']);
    $this->artisan('edlink:seed-autumn-demo', ['--school' => ['EDL-TEACH']])->assertSuccessful();
    expect(AttendanceRecord::count())->toBe(90);
    expect($existing->fresh()->note)->toBe('Preserve this existing register');
    expect(AttendanceRecord::whereDate('attendance_date', '2026-11-30')->count())->toBe(3);
    expect(AttendanceRecord::whereDate('attendance_date', '2026-10-31')->count())->toBe(0);
    expect(FeePayment::posted()->whereMonth('paid_at', 10)->count())->toBeGreaterThan(0);
    expect(FeePayment::posted()->whereMonth('paid_at', 11)->count())->toBeGreaterThan(0);
    expect(FeePayment::posted()->whereDate('paid_at', '2026-11-30')->count())->toBeGreaterThan(0);
    expect(Expense::posted()->count())->toBe(18);
    expect(AccountingJournal::where('status', 'posted')->count())->toBe(FeePayment::count() + Expense::count());
    foreach (AccountingJournal::with('lines')->get() as $journal) {
        expect((float) $journal->lines->sum('debit'))->toBe((float) $journal->lines->sum('credit'));
    }
    $counts = [AttendanceRecord::count(), FeePayment::count(), Expense::count(), AccountingJournal::count(), CashPoolEntry::count()];
    $this->artisan('edlink:seed-autumn-demo', ['--school' => ['EDL-TEACH']])->assertSuccessful();
    expect([AttendanceRecord::count(), FeePayment::count(), Expense::count(), AccountingJournal::count(), CashPoolEntry::count()])->toBe($counts);
    Mail::assertNothingSent();
});

it('rejects real schools and invalid dates without adding data', function () {
    School::create(['name' => 'Real School', 'slug' => 'real-school', 'school_number' => 'EDL-TEACH', 'is_demo' => false]);
    $this->artisan('edlink:seed-autumn-demo', ['--school' => ['EDL-TEACH']])->assertFailed();
    $this->artisan('edlink:seed-autumn-demo', ['--year' => '../bad'])->assertFailed();
    expect(FeePayment::count())->toBe(0)->and(AttendanceRecord::count())->toBe(0);
});
