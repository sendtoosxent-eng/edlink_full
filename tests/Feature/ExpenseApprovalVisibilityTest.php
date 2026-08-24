<?php

use App\Livewire\Expenses;
use App\Models\Expense;
use App\Models\FinanceLedgerEntry;
use App\Models\FinancialAccount;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use App\Services\FinanceLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows and totals only approved expenses', function () {
    $school = School::create(['name' => 'Expense Approval School', 'slug' => 'expense-approval-school']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $recorder = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $approver = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $cashAccountId = FinancialAccount::where('school_id', $school->id)->where('type', 'cash')->value('id');

    $pending = Expense::create([
        'school_id' => $school->id,
        'financial_account_id' => $cashAccountId,
        'term_id' => $term->id,
        'category' => 'Utilities',
        'amount' => 250000,
        'description' => 'Pending electricity expense',
        'expense_date' => now()->toDateString(),
        'reference_number' => 'PENDING-EXP-001',
        'recorded_by' => $recorder->id,
    ]);

    expect(Expense::posted()->count())->toBe(0);
    Livewire::actingAs($recorder)->test(Expenses::class)
        ->assertDontSee('PENDING-EXP-001')
        ->assertSee('UGX')
        ->assertSee('0');

    $ledger = FinanceLedgerEntry::where('source_type', Expense::class)->where('source_id', $pending->id)->firstOrFail();
    app(FinanceLedgerService::class)->approve($ledger, $approver->id);

    expect(Expense::posted()->pluck('id')->all())->toBe([$pending->id]);
    Livewire::actingAs($recorder)->test(Expenses::class)
        ->assertSee('PENDING-EXP-001')
        ->assertSee('250,000');

    $rejected = Expense::create([
        'school_id' => $school->id,
        'financial_account_id' => $cashAccountId,
        'term_id' => $term->id,
        'category' => 'Supplies',
        'amount' => 900000,
        'description' => 'Rejected supplies expense',
        'expense_date' => now()->toDateString(),
        'reference_number' => 'REJECTED-EXP-002',
        'recorded_by' => $recorder->id,
    ]);
    $rejectedLedger = FinanceLedgerEntry::where('source_type', Expense::class)->where('source_id', $rejected->id)->firstOrFail();
    app(FinanceLedgerService::class)->reject($rejectedLedger, 'Not authorized', $approver->id);

    expect(Expense::posted()->pluck('id')->all())->toBe([$pending->id]);
    Livewire::actingAs($recorder)->test(Expenses::class)
        ->assertSee('PENDING-EXP-001')
        ->assertDontSee('REJECTED-EXP-002')
        ->assertDontSee('900,000');
});
