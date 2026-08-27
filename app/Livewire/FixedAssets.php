<?php

namespace App\Livewire;

use App\Models\FinancialAccount;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetDepreciation;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\FixedAssetService;
use App\Services\FixedAssetSetupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FixedAssets extends Component
{
    use WithPagination;

    public string $tab = 'register';

    public string $categoryCode = '';

    public string $categoryName = '';

    public string $categoryLife = '60';

    public string $categoryMethod = 'straight_line';

    public string $categoryRate = '20';

    public string $assetAccountId = '';

    public string $accumulatedAccountId = '';

    public string $expenseAccountId = '';

    public string $assetTag = '';

    public string $name = '';

    public string $categoryId = '';

    public string $financialAccountId = '';

    public string $custodianId = '';

    public string $serialNumber = '';

    public string $location = '';

    public string $description = '';

    public string $acquisitionDate = '';

    public string $inServiceDate = '';

    public string $cost = '';

    public string $residualValue = '0';

    public string $settlementType = 'immediate';

    public string $depreciationDate = '';

    public string $search = '';

    public function mount(FixedAssetSetupService $setup): void
    {
        $this->authorizeAccess('accounting.assets.view');
        $setup->activate(Auth::user()->school);
        $this->acquisitionDate = $this->inServiceDate = now()->toDateString();
        $this->depreciationDate = now()->endOfMonth()->toDateString();
        $this->financialAccountId = (string) FinancialAccount::where('school_id', Auth::user()->school_id)->where('type', 'bank')->value('id');
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['register', 'categories', 'depreciation'], true), 404);
        $this->tab = $tab;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function saveCategory(): void
    {
        $this->authorizeAccess('accounting.assets.manage');
        $school = Auth::user()->school_id;
        $data = $this->validate(['categoryCode' => 'required|string|max:30', 'categoryName' => 'required|string|max:120', 'categoryLife' => 'required|integer|min:1|max:1200', 'categoryMethod' => 'required|in:none,straight_line,reducing_balance', 'categoryRate' => 'nullable|numeric|min:0.0001|max:100', 'assetAccountId' => ['required', Rule::exists('ledger_accounts', 'id')->where('school_id', $school)->where('account_class', 'asset')], 'accumulatedAccountId' => ['required', Rule::exists('ledger_accounts', 'id')->where('school_id', $school)->where('account_class', 'asset')], 'expenseAccountId' => ['required', Rule::exists('ledger_accounts', 'id')->where('school_id', $school)->where('account_class', 'expense')]]);
        FixedAssetCategory::create(['school_id' => $school, 'code' => strtoupper($data['categoryCode']), 'name' => $data['categoryName'], 'useful_life_months' => $data['categoryLife'], 'depreciation_method' => $data['categoryMethod'], 'annual_rate' => $data['categoryMethod'] === 'reducing_balance' ? $data['categoryRate'] : null, 'asset_account_id' => $data['assetAccountId'], 'accumulated_depreciation_account_id' => $data['accumulatedAccountId'], 'depreciation_expense_account_id' => $data['expenseAccountId']]);
        $this->reset('categoryCode', 'categoryName');
        session()->flash('status', 'Asset category and posting rules saved.');
    }

    public function saveAsset(FixedAssetService $service): void
    {
        $this->authorizeAccess('accounting.assets.manage');
        $school = Auth::user()->school_id;
        $data = $this->validate(['assetTag' => ['required', 'string', 'max:60', Rule::unique('fixed_assets', 'asset_tag')->where('school_id', $school)], 'name' => 'required|string|max:150', 'categoryId' => ['required', Rule::exists('fixed_asset_categories', 'id')->where('school_id', $school)], 'financialAccountId' => [$this->settlementType === 'immediate' ? 'required' : 'nullable', Rule::exists('financial_accounts', 'id')->where('school_id', $school)], 'custodianId' => ['nullable', Rule::exists('users', 'id')->where('school_id', $school)], 'serialNumber' => 'nullable|string|max:100', 'location' => 'nullable|string|max:150', 'description' => 'nullable|string|max:1000', 'acquisitionDate' => 'required|date', 'inServiceDate' => 'required|date|after_or_equal:acquisitionDate', 'cost' => 'required|numeric|min:0.01', 'residualValue' => 'required|numeric|min:0|lte:cost', 'settlementType' => 'required|in:immediate,credit']);
        $cat = FixedAssetCategory::where('school_id', $school)->findOrFail($data['categoryId']);
        $asset = FixedAsset::create(['school_id' => $school, 'fixed_asset_category_id' => $cat->id, 'financial_account_id' => $data['settlementType'] === 'immediate' ? $data['financialAccountId'] : null, 'custodian_id' => $data['custodianId'] ?: null, 'asset_tag' => strtoupper($data['assetTag']), 'name' => $data['name'], 'serial_number' => $data['serialNumber'] ?: null, 'location' => $data['location'] ?: null, 'description' => $data['description'] ?: null, 'acquisition_date' => $data['acquisitionDate'], 'in_service_date' => $data['inServiceDate'], 'cost' => $data['cost'], 'residual_value' => $data['residualValue'], 'useful_life_months' => $cat->useful_life_months, 'depreciation_method' => $cat->depreciation_method, 'annual_rate' => $cat->annual_rate, 'settlement_type' => $data['settlementType'], 'recorded_by' => Auth::id()]);
        $service->submitAcquisition($asset, Auth::id());
        $this->reset('assetTag', 'name', 'serialNumber', 'location', 'description', 'cost');
        $this->residualValue = '0';
        session()->flash('status', 'Asset registered and acquisition journal sent for independent approval.');
    }

    public function approveAsset(int $id, FixedAssetService $service): void
    {
        $this->authorizeAccess('accounting.assets.approve');
        $service->approveAcquisition(FixedAsset::where('school_id', Auth::user()->school_id)->findOrFail($id), Auth::id());
        session()->flash('status', 'Asset acquisition posted and asset activated.');
    }

    public function depreciate(int $id, FixedAssetService $service): void
    {
        $this->authorizeAccess('accounting.assets.depreciate');
        $service->submitDepreciation(FixedAsset::where('school_id', Auth::user()->school_id)->findOrFail($id), $this->depreciationDate, Auth::id());
        session()->flash('status', 'Depreciation journal submitted for independent approval.');
    }

    public function approveDepreciation(int $id, FixedAssetService $service): void
    {
        $this->authorizeAccess('accounting.assets.approve');
        $service->approveDepreciation(FixedAssetDepreciation::where('school_id', Auth::user()->school_id)->findOrFail($id), Auth::id());
        session()->flash('status', 'Depreciation posted to the general ledger.');
    }

    private function authorizeAccess(string $permission): void
    {
        abort_unless(Auth::user()->hasPermission($permission), 403);
    }

    public function render()
    {
        $school = Auth::user()->school_id;
        $search = trim($this->search);
        $assets = FixedAsset::where('school_id', $school)->with(['category', 'custodian', 'acquisitionJournal'])
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match->where('asset_tag', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('serial_number', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%")->orWhere('status', 'like', "%{$search}%")->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$search}%"))->orWhereHas('custodian', fn ($custodian) => $custodian->where('name', 'like', "%{$search}%"))))
            ->latest()->paginate(20);

        return view('livewire.fixed-assets', ['assets' => $assets, 'categories' => FixedAssetCategory::where('school_id', $school)->with(['assetAccount', 'accumulatedAccount', 'expenseAccount'])->orderBy('name')->get(), 'financialAccounts' => FinancialAccount::where('school_id', $school)->whereNotNull('ledger_account_id')->where('is_active', true)->get(), 'assetAccounts' => LedgerAccount::where('school_id', $school)->where('account_class', 'asset')->where('accepts_postings', true)->get(), 'expenseAccounts' => LedgerAccount::where('school_id', $school)->where('account_class', 'expense')->where('accepts_postings', true)->get(), 'staff' => User::where('school_id', $school)->whereNotIn('role', ['student', 'parent'])->orderBy('name')->get(), 'pendingDepreciations' => FixedAssetDepreciation::where('school_id', $school)->where('status', 'submitted')->with('asset')->latest()->get(), 'pageTitle' => 'Accounting · Asset Management']);
    }
}
