<?php

use App\Models\AccountingJournal;
use App\Models\Designation;
use App\Models\FinancialAccount;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\LedgerAccount;
use App\Models\School;
use App\Models\User;
use App\Services\FixedAssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('posts controlled asset acquisitions and monthly depreciation', function () {
    $school = School::create(['name' => 'Asset School', 'slug' => 'asset-school']);
    $maker = User::factory()->create(['school_id' => $school->id, 'role' => 'bursar']);
    $checker = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $category = FixedAssetCategory::create(['school_id' => $school->id, 'code' => 'ICT', 'name' => 'ICT Equipment', 'useful_life_months' => 12, 'depreciation_method' => 'straight_line', 'asset_account_id' => LedgerAccount::where('school_id', $school->id)->where('code', '1540')->value('id'), 'accumulated_depreciation_account_id' => LedgerAccount::where('school_id', $school->id)->where('code', '1590')->value('id'), 'depreciation_expense_account_id' => LedgerAccount::where('school_id', $school->id)->where('code', '5600')->value('id')]);
    $bank = FinancialAccount::where('school_id', $school->id)->where('type', 'bank')->firstOrFail();
    $asset = FixedAsset::create(['school_id' => $school->id, 'fixed_asset_category_id' => $category->id, 'financial_account_id' => $bank->id, 'asset_tag' => 'ICT-001', 'name' => 'Computer Lab Server', 'acquisition_date' => now(), 'in_service_date' => now(), 'cost' => '120000.00', 'residual_value' => '0', 'useful_life_months' => 12, 'depreciation_method' => 'straight_line', 'settlement_type' => 'immediate', 'recorded_by' => $maker->id]);
    $service = app(FixedAssetService::class);
    $acquisition = $service->submitAcquisition($asset, $maker->id);
    $service->approveAcquisition($asset->fresh(), $checker->id);
    expect($acquisition->fresh()->status)->toBe('posted')->and($asset->fresh()->status)->toBe('active')->and((float) $acquisition->lines()->sum('debit'))->toBe(120000.0)->and((float) $acquisition->lines()->sum('credit'))->toBe(120000.0);
    $depreciation = $service->submitDepreciation($asset->fresh(), now()->endOfMonth()->toDateString(), $maker->id);
    $service->approveDepreciation($depreciation->fresh(), $checker->id);
    expect($depreciation->fresh()->status)->toBe('posted')->and((float) $depreciation->depreciation_amount)->toBe(10000.0)->and($asset->fresh()->carryingValue())->toBe(110000.0)->and(AccountingJournal::where('journal_type', 'asset_depreciation')->where('status', 'posted')->count())->toBe(1);
});

it('shows asset management as an accounting submenu', function () {
    $school = School::create(['name' => 'Asset Menu School', 'slug' => 'asset-menu-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $this->actingAs($admin)->get(route('accounting.assets'))->assertOk()->assertSee('Fixed Asset Management')->assertSee('Asset Register');
});

it('shows asset management to an existing accounting designation', function () {
    $school = School::create(['name' => 'Asset Access School', 'slug' => 'asset-access-school']);
    $designation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Accountant',
        'permissions' => ['accounting.dashboard.view', 'accounting.assets.view'],
    ]);
    $accountant = User::factory()->create([
        'school_id' => $school->id,
        'designation_id' => $designation->id,
        'role' => 'staff',
    ]);

    $this->actingAs($accountant)->get(route('accounting.index'))
        ->assertOk()
        ->assertSee('Asset Management');
});
