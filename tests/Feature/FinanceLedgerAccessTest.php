<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows administrators and bursars to access accounting reconciliation', function () {
    $school = School::create(['name' => 'Ledger School', 'slug' => 'ledger-school', 'status' => 'active', 'is_demo' => false]);
    $bursarDesignation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Bursar',
        'permissions' => ['finance.ledger'],
    ]);
    $administrator = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $bursar = User::factory()->create(['school_id' => $school->id, 'role' => 'bursar', 'designation_id' => $bursarDesignation->id]);

    $this->actingAs($administrator)->get(route('accounting.reconciliations'))->assertOk();
    $this->actingAs($bursar)->get(route('accounting.reconciliations'))->assertOk();
    $this->get(route('finance.ledger'))->assertRedirect('/accounting/reconciliation');
});

it('rejects foreign accounts and future reconciliation dates', function () {
    $school = School::create(['name' => 'Validation School', 'slug' => 'validation-school', 'status' => 'active', 'is_demo' => false]);
    $otherSchool = School::create(['name' => 'Foreign School', 'slug' => 'foreign-school', 'status' => 'active', 'is_demo' => false]);
    $foreignAccount = \App\Models\FinancialAccount::where('school_id', $otherSchool->id)->firstOrFail();
    $administrator = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    $this->actingAs($administrator)->post(route('accounting.reconciliations.store'), [
        'financial_account_id' => $foreignAccount->id,
        'period_ending' => now()->addDay()->toDateString(),
        'statement_balance' => 100,
    ])->assertSessionHasErrors(['financial_account_id', 'period_ending']);

    $this->assertDatabaseMissing('finance_reconciliations', ['school_id' => $school->id]);
});

it('denies the finance ledger to staff without its assigned permission', function () {
    $school = School::create(['name' => 'Restricted Ledger School', 'slug' => 'restricted-ledger-school', 'status' => 'active', 'is_demo' => false]);
    $teacherDesignation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Teacher',
        'permissions' => ['academics.subjects'],
    ]);
    $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher', 'designation_id' => $teacherDesignation->id]);

    $this->actingAs($teacher)->get(route('accounting.reconciliations'))->assertForbidden();
});
it('shows the saved reconciliation result and history', function () {
    $school = School::create(['name' => 'Reconciliation School', 'slug' => 'reconciliation-school', 'status' => 'active', 'is_demo' => false]);
    $account = \App\Models\FinancialAccount::where('school_id', $school->id)->where('type', 'cash')->firstOrFail();
    $administrator = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    $this->actingAs($administrator)->post(route('accounting.reconciliations.store'), [
        'financial_account_id' => $account->id,
        'period_ending' => '2026-07-30',
        'statement_balance' => 300000,
        'notes' => 'Opening statement check',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('finance_reconciliations', [
        'school_id' => $school->id,
        'statement_balance' => 300000,
        'ledger_balance' => 0,
        'difference' => 300000,
    ]);

    $this->actingAs($administrator)->get(route('accounting.reconciliations'))
        ->assertOk()
        ->assertSee('Reconciliation history')
        ->assertSee('UGX 300,000.00')
        ->assertSee('No posted ledger balance was found');
});
it('posts approved entries to the pool and reverses both records atomically', function () {
    $school = School::create(['name'=>'Controlled Finance School','slug'=>'controlled-finance-school','status'=>'active','is_demo'=>false]);
    $account = \App\Models\FinancialAccount::where('school_id',$school->id)->where('type','cash')->firstOrFail();
    $recorder = User::factory()->create(['school_id'=>$school->id,'role'=>'bursar']);
    $approver = User::factory()->create(['school_id'=>$school->id,'role'=>'admin']);
    $account = \App\Models\FinancialAccount::where('school_id',$school->id)->where('type','cash')->firstOrFail();
    $entry = \App\Models\FinanceLedgerEntry::create(['school_id'=>$school->id,'financial_account_id'=>$account->id,'reference'=>'TEST-CONTROL-1','entry_type'=>'expense','direction'=>'debit','amount'=>500000,'description'=>'Controlled test','status'=>'pending','recorded_by'=>$recorder->id,'posted_at'=>now()]);
    $service = app(\App\Services\FinanceLedgerService::class);
    $service->approve($entry, $approver->id);
    expect($entry->fresh()->status)->toBe('posted');
    $this->assertDatabaseHas('cash_pool_entries',['finance_ledger_entry_id'=>$entry->id,'direction'=>'debit','amount'=>500000]);
    $reversal = $service->reverse($entry->fresh(), 'Incorrect controlled test entry', $approver->id);
    expect($entry->fresh()->status)->toBe('reversed')->and($reversal->direction)->toBe('credit');
    $this->assertDatabaseHas('cash_pool_entries',['finance_ledger_entry_id'=>$reversal->id,'direction'=>'credit','amount'=>500000]);
    $reconciliation = $service->reconcile($school->id, $account->id, now()->toDateString(), 0, null, $approver->id);
    expect((float)$reconciliation->ledger_balance)->toBe(0.0)->and((float)$reconciliation->difference)->toBe(0.0);
});
