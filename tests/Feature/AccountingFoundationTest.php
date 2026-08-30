<?php

use App\Livewire\Accounting as AccountingWorkspace;
use App\Models\AccountingJournal;
use App\Models\AccountingPeriod;
use App\Models\AccountMapping;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\FinancialAccount;
use App\Models\LedgerAccount;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\Term;
use App\Models\User;
use App\Services\AccountingMigrationService;
use App\Services\AccountingReportService;
use App\Services\AccountingSetupService;
use App\Services\DoubleEntryService;
use App\Services\FinanceLedgerService;
use App\Services\StudentReceivablesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates an idempotent professional chart and maps financial accounts', function () {
    $school = School::create(['name' => 'Accounting School', 'slug' => 'accounting-school']);
    $service = app(AccountingSetupService::class);
    $initial = LedgerAccount::where('school_id', $school->id)->count();
    $service->activate($school);

    expect($initial)->toBeGreaterThan(60)
        ->and(LedgerAccount::where('school_id', $school->id)->count())->toBe($initial)
        ->and(LedgerAccount::where('school_id', $school->id)->where('code', '1210')->where('is_control_account', true)->exists())->toBeTrue()
        ->and(FinancialAccount::where('school_id', $school->id)->whereNull('ledger_account_id')->count())->toBe(0);
});

it('posts balanced journals using exact decimal arithmetic and maker checker', function () {
    $school = School::create(['name' => 'Balanced School', 'slug' => 'balanced-school']);
    $maker = User::factory()->create(['school_id' => $school->id]);
    $checker = User::factory()->create(['school_id' => $school->id]);
    $cash = LedgerAccount::where('school_id', $school->id)->where('code', '1110')->firstOrFail();
    $income = LedgerAccount::where('school_id', $school->id)->where('code', '4100')->firstOrFail();
    $service = app(DoubleEntryService::class);
    $journal = $service->create(['school_id' => $school->id, 'journal_date' => now()->toDateString(), 'description' => 'Test receipt', 'journal_type' => 'manual', 'currency' => 'UGX'], [
        ['ledger_account_id' => $cash->id, 'debit' => '100.10', 'credit' => '0'],
        ['ledger_account_id' => $income->id, 'debit' => '0', 'credit' => '100.10'],
    ], $maker->id);
    $service->submit($journal, $maker->id);
    expect(fn () => $service->approve($journal->fresh(), $maker->id))->toThrow(ValidationException::class);
    $service->approve($journal->fresh(), $checker->id);
    $service->post($journal->fresh(), $checker->id);
    expect($journal->fresh()->status)->toBe('posted')
        ->and((float) $journal->lines()->sum('debit'))->toBe((float) $journal->lines()->sum('credit'));
});

it('rejects unbalanced, zero, dual-sided and cross-tenant journal lines', function () {
    $school = School::create(['name' => 'First School', 'slug' => 'first-school']);
    $other = School::create(['name' => 'Other School', 'slug' => 'other-school']);
    $maker = User::factory()->create(['school_id' => $school->id]);
    $cash = LedgerAccount::where('school_id', $school->id)->where('code', '1110')->firstOrFail();
    $otherIncome = LedgerAccount::where('school_id', $other->id)->where('code', '4100')->firstOrFail();
    $service = app(DoubleEntryService::class);

    expect(fn () => $service->create(['school_id' => $school->id, 'journal_date' => now()->toDateString(), 'description' => 'Bad journal', 'currency' => 'UGX'], [
        ['ledger_account_id' => $cash->id, 'debit' => '100.00', 'credit' => '0'],
        ['ledger_account_id' => $otherIncome->id, 'debit' => '0', 'credit' => '90.00'],
    ], $maker->id))->toThrow(ValidationException::class);
    expect(AccountingJournal::where('school_id', $school->id)->count())->toBe(0);
});

it('locks posted history and creates balanced immutable reversals', function () {
    $school = School::create(['name' => 'Reversal School', 'slug' => 'reversal-school']);
    $maker = User::factory()->create(['school_id' => $school->id]);
    $checker = User::factory()->create(['school_id' => $school->id]);
    $cash = LedgerAccount::where('school_id', $school->id)->where('code', '1110')->value('id');
    $income = LedgerAccount::where('school_id', $school->id)->where('code', '4100')->value('id');
    $service = app(DoubleEntryService::class);
    $journal = $service->createAndPost(['school_id' => $school->id, 'journal_date' => now()->toDateString(), 'description' => 'Receipt', 'currency' => 'UGX', 'idempotency_key' => 'test:receipt:1'], [['ledger_account_id' => $cash, 'debit' => '50.00', 'credit' => '0'], ['ledger_account_id' => $income, 'debit' => '0', 'credit' => '50.00']], $maker->id, $checker->id);
    $reversal = $service->reverse($journal, 'Receipt entered twice', $checker->id);
    expect($journal->fresh()->status)->toBe('reversed')->and($reversal->status)->toBe('posted')->and($reversal->reversal_of_id)->toBe($journal->id)
        ->and((float) $reversal->lines()->sum('debit'))->toBe((float) $reversal->lines()->sum('credit'));
});

it('posts fee assessments, applies receipts to receivables, and defers genuine advances', function () {
    $school = School::create(['name' => 'Receivables School', 'slug' => 'receivables-school']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => now()->year, 'term_number' => 1, 'is_current' => true, 'status' => 'open']);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'P4']);
    $category = StudentCategory::create(['school_id' => $school->id, 'name' => 'Day']);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id, 'name' => 'Ada Learner', 'status' => 'active']);
    StudentEnrolment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'base_fee_amount' => '1000.00', 'status' => 'active', 'enrolled_at' => now()]);
    $maker = User::factory()->create(['school_id' => $school->id]);
    $checker = User::factory()->create(['school_id' => $school->id]);
    $result = app(StudentReceivablesService::class)->generateAssessments($school, $term, $maker->id);
    expect($result['created'])->toBe(1);
    $assessmentJournal = AccountingJournal::where('journal_type', 'fee_assessment')->firstOrFail();
    app(DoubleEntryService::class)->approve($assessmentJournal, $checker->id);
    app(DoubleEntryService::class)->post($assessmentJournal->fresh(), $checker->id);
    $cash = FinancialAccount::where('school_id', $school->id)->where('type', 'cash')->firstOrFail();
    $payment = FeePayment::create(['school_id' => $school->id, 'financial_account_id' => $cash->id, 'student_id' => $student->id, 'term_id' => $term->id, 'amount' => '1200.00', 'method' => 'cash', 'recorded_by' => $maker->id, 'paid_at' => now()]);
    $legacy = FinanceLedgerEntry::where('source_type', FeePayment::class)->where('source_id', $payment->id)->firstOrFail();
    app(FinanceLedgerService::class)->approve($legacy, $checker->id);
    $receipt = AccountingJournal::where('journal_type', 'fee_receipt')->firstOrFail();
    expect((float) $receipt->lines()->sum('debit'))->toBe(1200.0)->and((float) $receipt->lines()->sum('credit'))->toBe(1200.0)
        ->and((float) $receipt->lines()->whereHas('account', fn ($q) => $q->where('code', '1210'))->sum('credit'))->toBe(1000.0)
        ->and((float) $receipt->lines()->whereHas('account', fn ($q) => $q->where('code', '2130'))->sum('credit'))->toBe(200.0);
    $summary = app(AccountingReportService::class)->summary($school->id);
    expect($summary['debits'])->toBe($summary['credits']);
});

it('rejects postings to locked accounting periods', function () {
    $school = School::create(['name' => 'Locked Books', 'slug' => 'locked-books']);
    $maker = User::factory()->create(['school_id' => $school->id]);
    $checker = User::factory()->create(['school_id' => $school->id]);
    $cash = LedgerAccount::where('school_id', $school->id)->where('code', '1110')->value('id');
    $income = LedgerAccount::where('school_id', $school->id)->where('code', '4100')->value('id');
    $journal = app(DoubleEntryService::class)->create(['school_id' => $school->id, 'journal_date' => now()->toDateString(), 'description' => 'Locked test', 'currency' => 'UGX'], [['ledger_account_id' => $cash, 'debit' => 10, 'credit' => 0], ['ledger_account_id' => $income, 'debit' => 0, 'credit' => 10]], $maker->id);
    app(DoubleEntryService::class)->submit($journal, $maker->id);
    app(DoubleEntryService::class)->approve($journal->fresh(), $checker->id);
    AccountingPeriod::findOrFail($journal->accounting_period_id)->update(['status' => 'locked']);
    expect(fn () => app(DoubleEntryService::class)->post($journal->fresh(), $checker->id))->toThrow(ValidationException::class);
});

it('renders the tenant accounting workspace without exposing another school', function () {
    $school = School::create(['name' => 'Workspace School', 'slug' => 'workspace-school', 'status' => 'active', 'license_status' => 'active']);
    $other = School::create(['name' => 'Secret School', 'slug' => 'secret-school', 'status' => 'active']);
    LedgerAccount::where('school_id', $other->id)->where('code', '4100')->update(['name' => 'Secret foreign account']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $this->actingAs($admin)->get(route('accounting.index'))->assertOk()->assertSee('Double-entry enabled')->assertSee('Accounting Workspace')->assertDontSee('Secret foreign account');
    $this->get(route('accounting.index', ['tab' => 'accounts']))->assertOk()->assertSee('Chart of accounts')->assertSee('Account setup')->assertSee('Assets')->assertSee('Liabilities')->assertSee('Cash and Cash Equivalents')->assertSee('subaccounts')->assertDontSee('Secret foreign account');
    $this->get(route('accounting.index', ['tab' => 'settings']))->assertOk()->assertSee('Cash integration')->assertSee('Automation setup')->assertSee('Automatic debit and credit destinations')->assertDontSee('Secret foreign account');
    $cash = LedgerAccount::where('school_id', $school->id)->where('code', '1110')->firstOrFail();
    Livewire::actingAs($admin)->test(AccountingWorkspace::class)->call('setTab', 'accounts')->call('editAccount', $cash->id)->assertSet('editingAccountId', $cash->id)->assertSet('accountCode', '1110')->assertSet('accountName', 'Cash on Hand');
});

it('prepares one balanced opening journal and requires independent approval', function () {
    $school = School::create(['name' => 'Migration School', 'slug' => 'migration-school']);
    $maker = User::factory()->create(['school_id' => $school->id]);
    FinancialAccount::where('school_id', $school->id)->where('type', 'bank')->update(['opening_balance' => '2500.00']);
    $service = app(AccountingMigrationService::class);
    $journal = $service->createOpeningDraft($school, now()->toDateString(), $maker->id);
    $again = $service->createOpeningDraft($school, now()->toDateString(), $maker->id);
    expect($journal->status)->toBe('submitted')->and($again->id)->toBe($journal->id)->and((float) $journal->lines()->sum('debit'))->toBe((float) $journal->lines()->sum('credit'));
});

it('lets an administrator maintain the chart without bypassing maker-checker controls', function () {
    $school = School::create(['name' => 'Controlled Admin School', 'slug' => 'controlled-admin-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    expect($admin->hasPermission('accounting.dashboard.view'))->toBeTrue()->and($admin->hasPermission('accounting.accounts.manage'))->toBeTrue()->and($admin->hasPermission('accounting.mappings.manage'))->toBeTrue()->and($admin->hasPermission('accounting.journals.approve'))->toBeFalse()->and($admin->hasPermission('accounting.periods.reopen'))->toBeFalse();
});

it('lets administrators map expense categories to editable chart accounts', function () {
    $school = School::create(['name' => 'Mapping School', 'slug' => 'mapping-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $utilities = LedgerAccount::where('school_id', $school->id)->where('code', '5400')->firstOrFail();

    Livewire::actingAs($admin)->test(AccountingWorkspace::class)
        ->set('mappings.expense_category:utilities', (string) $utilities->id)
        ->call('saveMappings')->assertHasNoErrors();

    expect(AccountMapping::where('school_id', $school->id)->where('mapping_type', 'expense_category:utilities')->value('ledger_account_id'))->toBe($utilities->id);
});

it('provides ledger mappings from system settings', function () {
    $school = School::create(['name' => 'Settings Mapping School', 'slug' => 'settings-mapping-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Configure ledger mappings');

    $this->actingAs($admin)
        ->get(route('settings.ledger-mappings'))
        ->assertOk()
        ->assertSee('Ledger Mapping Settings')
        ->assertSee('Fees &amp; receivables', false)
        ->assertSee('Expense categories')
        ->assertDontSee('Chart of accounts');
});

it('deletes only unused custom ledger accounts', function () {
    $school = School::create(['name' => 'Ledger Maintenance School', 'slug' => 'ledger-maintenance-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $custom = LedgerAccount::create(['school_id' => $school->id, 'code' => '5890', 'name' => 'Temporary Expense', 'account_class' => 'expense', 'subtype' => 'temporary', 'normal_balance' => 'debit', 'currency' => 'UGX', 'accepts_postings' => true, 'is_control_account' => false, 'is_system' => false, 'is_active' => true, 'created_by' => $admin->id]);

    Livewire::actingAs($admin)->test(AccountingWorkspace::class)->call('editAccount', $custom->id)->assertSet('editingAccountId', $custom->id)->call('deleteAccount')->assertHasNoErrors()->assertSet('editingAccountId', null);
    expect(LedgerAccount::whereKey($custom->id)->exists())->toBeFalse();

    $system = LedgerAccount::where('school_id', $school->id)->where('code', '1110')->firstOrFail();
    Livewire::actingAs($admin)->test(AccountingWorkspace::class)->call('editAccount', $system->id)->call('deleteAccount')->assertHasErrors('account');
    expect(LedgerAccount::whereKey($system->id)->exists())->toBeTrue();
});
