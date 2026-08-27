<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AuditLog;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\LedgerAccount;
use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FixedAssetService
{
    public function __construct(private readonly DoubleEntryService $journals, private readonly AccountingSetupService $setup) {}

    public function submitAcquisition(FixedAsset $asset, int $userId): AccountingJournal
    {
        return DB::transaction(function () use ($asset, $userId) {
            $asset = FixedAsset::with(['category', 'financialAccount.ledgerAccount'])->lockForUpdate()->findOrFail($asset->id);
            if ($asset->acquisition_journal_id) {
                return $asset->acquisitionJournal;
            }
            $credit = $asset->settlement_type === 'credit' ? LedgerAccount::where('school_id', $asset->school_id)->where('code', '2100')->firstOrFail() : $asset->financialAccount?->ledgerAccount;
            if (! $credit) {
                throw ValidationException::withMessages(['financial_account_id' => 'Select a mapped financial account.']);
            }
            $this->setup->ensurePeriods(School::findOrFail($asset->school_id), (int) $asset->acquisition_date->format('Y'));
            $journal = $this->journals->create(['school_id' => $asset->school_id, 'journal_date' => $asset->acquisition_date->toDateString(), 'reference' => 'ASSET-'.$asset->asset_tag, 'description' => 'Acquisition: '.$asset->name, 'journal_type' => 'asset_acquisition', 'currency' => $this->currency($asset->school_id), 'source_type' => FixedAsset::class, 'source_id' => $asset->id, 'idempotency_key' => 'asset_acquisition:'.$asset->id], [['ledger_account_id' => $asset->category->asset_account_id, 'description' => $asset->asset_tag.' '.$asset->name, 'debit' => $asset->cost, 'credit' => 0], ['ledger_account_id' => $credit->id, 'description' => $asset->settlement_type === 'credit' ? 'Supplier payable' : 'Asset payment', 'debit' => 0, 'credit' => $asset->cost]], $userId);
            $this->journals->submit($journal, $userId);
            $asset->update(['acquisition_journal_id' => $journal->id, 'status' => 'awaiting_approval']);
            AuditLog::record($asset->school_id, 'accounting.asset.submitted', $asset);

            return $journal;
        });
    }

    public function approveAcquisition(FixedAsset $asset, int $userId): void
    {
        DB::transaction(function () use ($asset, $userId) {
            $journal = $asset->acquisitionJournal()->firstOrFail();
            $this->journals->approve($journal, $userId);
            $this->journals->post($journal->fresh(), $userId);
            $asset->update(['status' => 'active']);
            AuditLog::record($asset->school_id, 'accounting.asset.activated', $asset);
        });
    }

    public function submitDepreciation(FixedAsset $asset, string $periodEnding, int $userId): FixedAssetDepreciation
    {
        return DB::transaction(function () use ($asset, $periodEnding, $userId) {
            $asset = FixedAsset::with('category')->lockForUpdate()->findOrFail($asset->id);
            if ($asset->status !== 'active') {
                throw ValidationException::withMessages(['asset' => 'Only active assets can be depreciated.']);
            }
            if ($asset->depreciation_method === 'none') {
                throw ValidationException::withMessages(['asset' => 'This asset category is configured as non-depreciable.']);
            }
            if ($periodEnding < $asset->in_service_date->toDateString()) {
                throw ValidationException::withMessages(['asset' => 'Depreciation cannot be posted before the in-service date.']);
            }
            if (FixedAssetDepreciation::where('fixed_asset_id', $asset->id)->whereDate('period_ending', $periodEnding)->exists()) {
                throw ValidationException::withMessages(['asset' => 'Depreciation already exists for this asset and period.']);
            }
            $this->setup->ensurePeriods(School::findOrFail($asset->school_id), (int) substr($periodEnding, 0, 4));
            $opening = $asset->carryingValue();
            $depreciable = max(0, $opening - (float) $asset->residual_value);
            $amount = $asset->depreciation_method === 'reducing_balance' ? round($opening * ((float) $asset->annual_rate / 100) / 12, 2) : round(((float) $asset->cost - (float) $asset->residual_value) / max(1, $asset->useful_life_months), 2);
            $amount = min($depreciable, $amount);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['asset' => 'This asset is fully depreciated.']);
            }
            $dep = FixedAssetDepreciation::create(['school_id' => $asset->school_id, 'fixed_asset_id' => $asset->id, 'period_ending' => $periodEnding, 'opening_carrying_value' => $opening, 'depreciation_amount' => $amount, 'closing_carrying_value' => $opening - $amount, 'status' => 'submitted', 'prepared_by' => $userId]);
            $journal = $this->journals->create(['school_id' => $asset->school_id, 'journal_date' => $periodEnding, 'reference' => 'DEP-'.$asset->asset_tag.'-'.str_replace('-', '', $periodEnding), 'description' => 'Depreciation: '.$asset->name, 'journal_type' => 'asset_depreciation', 'currency' => $this->currency($asset->school_id), 'source_type' => FixedAssetDepreciation::class, 'source_id' => $dep->id, 'idempotency_key' => 'asset_depreciation:'.$dep->id], [['ledger_account_id' => $asset->category->depreciation_expense_account_id, 'description' => 'Depreciation expense '.$asset->asset_tag, 'debit' => $amount, 'credit' => 0], ['ledger_account_id' => $asset->category->accumulated_depreciation_account_id, 'description' => 'Accumulated depreciation '.$asset->asset_tag, 'debit' => 0, 'credit' => $amount]], $userId);
            $this->journals->submit($journal, $userId);
            $dep->update(['journal_id' => $journal->id]);
            AuditLog::record($asset->school_id, 'accounting.asset.depreciation_submitted', $dep);

            return $dep;
        });
    }

    public function approveDepreciation(FixedAssetDepreciation $dep, int $userId): void
    {
        DB::transaction(function () use ($dep, $userId) {
            $journal = $dep->journal()->firstOrFail();
            $this->journals->approve($journal, $userId);
            $this->journals->post($journal->fresh(), $userId);
            $dep->update(['status' => 'posted', 'approved_by' => $userId, 'posted_at' => now()]);
            $asset = $dep->asset;
            if ($asset->carryingValue() <= (float) $asset->residual_value) {
                $asset->update(['status' => 'fully_depreciated']);
            }AuditLog::record($dep->school_id, 'accounting.asset.depreciation_posted', $dep);
        });
    }

    private function currency(int $schoolId): string
    {
        return strtoupper((string) SchoolSetting::getValue($schoolId, 'accounting_currency', 'UGX'));
    }
}
