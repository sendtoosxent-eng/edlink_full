<?php

namespace App\Services;

use App\Models\AccountingFund;
use App\Models\AccountingPeriod;
use App\Models\AccountMapping;
use App\Models\AuditLog;
use App\Models\CostCentre;
use App\Models\FinancialAccount;
use App\Models\FiscalYear;
use App\Models\LedgerAccount;
use App\Models\School;
use App\Models\SchoolSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AccountingSetupService
{
    public const DEFAULT_ACCOUNTS = [
        ['1000', 'Assets', 'asset', 'header', 'debit', false],
        ['1100', 'Cash and Cash Equivalents', 'asset', 'cash_and_equivalents', 'debit', false, '1000'],
        ['1110', 'Cash on Hand', 'asset', 'cash', 'debit', true, '1100'], ['1120', 'Petty Cash', 'asset', 'petty_cash', 'debit', true, '1100'],
        ['1130', 'Main Bank Account', 'asset', 'bank', 'debit', true, '1100'], ['1140', 'Additional Bank Accounts', 'asset', 'bank', 'debit', true, '1100'], ['1150', 'Mobile Money', 'asset', 'mobile_money', 'debit', true, '1100'],
        ['1200', 'Receivables', 'asset', 'receivable', 'debit', false, '1000'], ['1210', 'Student Fee Receivables', 'asset', 'student_receivable', 'debit', true, '1200'], ['1220', 'Other Receivables', 'asset', 'other_receivable', 'debit', true, '1200'], ['1230', 'Staff Advances', 'asset', 'staff_advance', 'debit', true, '1200'],
        ['1300', 'Inventory', 'asset', 'inventory', 'debit', true, '1000'], ['1400', 'Prepayments', 'asset', 'prepayment', 'debit', true, '1000'],
        ['1500', 'Property, Plant and Equipment', 'asset', 'fixed_asset', 'debit', false, '1000'], ['1510', 'Land', 'asset', 'fixed_asset', 'debit', true, '1500'], ['1520', 'Buildings', 'asset', 'fixed_asset', 'debit', true, '1500'], ['1530', 'Furniture and Equipment', 'asset', 'fixed_asset', 'debit', true, '1500'], ['1540', 'Computers and ICT Equipment', 'asset', 'fixed_asset', 'debit', true, '1500'], ['1550', 'Vehicles', 'asset', 'fixed_asset', 'debit', true, '1500'], ['1590', 'Accumulated Depreciation', 'asset', 'contra_asset', 'credit', true, '1500'],
        ['2000', 'Liabilities', 'liability', 'header', 'credit', false], ['2100', 'Supplier Payables', 'liability', 'payable', 'credit', true, '2000'], ['2110', 'Salaries Payable', 'liability', 'payroll_liability', 'credit', true, '2000'], ['2120', 'Statutory Deductions Payable', 'liability', 'statutory_liability', 'credit', true, '2000'], ['2130', 'Fees Received in Advance', 'liability', 'deferred_income', 'credit', true, '2000'], ['2140', 'Student and Caution Deposits', 'liability', 'deposit', 'credit', true, '2000'], ['2150', 'Taxes and Statutory Obligations', 'liability', 'tax', 'credit', true, '2000'], ['2200', 'Short-Term Loans', 'liability', 'loan', 'credit', true, '2000'], ['2300', 'Long-Term Loans', 'liability', 'loan', 'credit', true, '2000'],
        ['3000', 'Equity and Funds', 'equity', 'header', 'credit', false], ['3100', 'Opening Fund Balance', 'equity', 'opening_balance', 'credit', true, '3000'], ['3200', 'Accumulated Surplus or Deficit', 'equity', 'retained_surplus', 'credit', true, '3000'], ['3300', 'Current-Year Surplus or Deficit', 'equity', 'current_surplus', 'credit', true, '3000'], ['3400', 'Restricted Funds', 'equity', 'restricted_fund', 'credit', true, '3000'], ['3500', 'Development or Capital Fund', 'equity', 'capital_fund', 'credit', true, '3000'],
        ['4000', 'Income', 'income', 'header', 'credit', false], ['4100', 'Tuition Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4110', 'Admission and Registration Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4120', 'Examination Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4130', 'Computer and Laboratory Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4140', 'Development Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4150', 'Boarding Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4160', 'Transport Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4170', 'Meals Income', 'income', 'fee_income', 'credit', true, '4000'], ['4180', 'Medical Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4190', 'Activity and Sports Fees', 'income', 'fee_income', 'credit', true, '4000'], ['4200', 'Uniform Sales', 'income', 'sales', 'credit', true, '4000'], ['4210', 'Books and Stationery Sales', 'income', 'sales', 'credit', true, '4000'], ['4300', 'Grants and Donations', 'income', 'grant', 'credit', true, '4000'], ['4400', 'Rental Income', 'income', 'rental', 'credit', true, '4000'], ['4500', 'Interest Income', 'income', 'interest', 'credit', true, '4000'], ['4900', 'Other Income', 'income', 'other_income', 'credit', true, '4000'], ['4950', 'Fee Discounts', 'income', 'contra_income', 'debit', true, '4000'],
        ['5000', 'Expenses', 'expense', 'header', 'debit', false], ['5100', 'Teaching Salaries', 'expense', 'payroll', 'debit', true, '5000'], ['5110', 'Non-Teaching Salaries', 'expense', 'payroll', 'debit', true, '5000'], ['5120', 'Staff Benefits and Allowances', 'expense', 'payroll', 'debit', true, '5000'], ['5200', 'Teaching Materials', 'expense', 'academic', 'debit', true, '5000'], ['5210', 'Examination Expenses', 'expense', 'academic', 'debit', true, '5000'], ['5220', 'Laboratory and Library Expenses', 'expense', 'academic', 'debit', true, '5000'], ['5230', 'Sports and Co-curricular Activities', 'expense', 'academic', 'debit', true, '5000'], ['5240', 'Staff Training', 'expense', 'staff', 'debit', true, '5000'], ['5300', 'Food and Boarding Expenses', 'expense', 'boarding', 'debit', true, '5000'], ['5310', 'Transport and Vehicle Expenses', 'expense', 'transport', 'debit', true, '5000'], ['5400', 'Utilities', 'expense', 'utilities', 'debit', true, '5000'], ['5410', 'Repairs and Maintenance', 'expense', 'maintenance', 'debit', true, '5000'], ['5420', 'Rent and Property Expenses', 'expense', 'property', 'debit', true, '5000'], ['5430', 'Security and Cleaning', 'expense', 'operations', 'debit', true, '5000'], ['5440', 'ICT and Software', 'expense', 'ict', 'debit', true, '5000'], ['5450', 'Printing and Communication', 'expense', 'communication', 'debit', true, '5000'], ['5460', 'Insurance', 'expense', 'insurance', 'debit', true, '5000'], ['5500', 'Office and Administration', 'expense', 'administration', 'debit', true, '5000'], ['5510', 'Professional and Audit Fees', 'expense', 'professional', 'debit', true, '5000'], ['5520', 'Bank and Mobile-Money Charges', 'expense', 'bank_charge', 'debit', true, '5000'], ['5530', 'Interest Expense', 'expense', 'interest', 'debit', true, '5000'], ['5600', 'Depreciation', 'expense', 'depreciation', 'debit', true, '5000'], ['5700', 'Scholarships and Bursaries', 'expense', 'scholarship', 'debit', true, '5000'], ['5710', 'Bad-Debt Expense', 'expense', 'bad_debt', 'debit', true, '5000'], ['5790', 'Losses and Adjustments', 'expense', 'adjustment', 'debit', true, '5000'], ['5795', 'Rounding Differences', 'expense', 'rounding', 'debit', true, '5000'],
    ];

    public const DEFAULT_MAPPINGS = [
        'student_receivable' => '1210', 'staff_advance' => '1230', 'default_fee_income' => '4100', 'fees_received_in_advance' => '2130', 'fee_discount' => '4950', 'scholarship' => '5700', 'bad_debt' => '5710', 'supplier_payable' => '2100', 'teaching_salary_expense' => '5100', 'non_teaching_salary_expense' => '5110', 'staff_benefits_expense' => '5120', 'salaries_payable' => '2110', 'statutory_deductions_payable' => '2120', 'bank_charges' => '5520', 'rounding_differences' => '5795', 'opening_balance' => '3100', 'retained_surplus' => '3200', 'default_expense' => '5500',
    ];

    public function activate(School $school, ?int $userId = null): void
    {
        DB::transaction(function () use ($school, $userId): void {
            $wasEnabled = SchoolSetting::getValue($school->id, 'accounting_enabled') === 'enabled';
            $currency = strtoupper((string) SchoolSetting::getValue($school->id, 'currency', 'UGX'));
            foreach (self::DEFAULT_ACCOUNTS as $definition) {
                [$code,$name,$class,$subtype,$normal,$posting] = array_slice($definition, 0, 6);
                $parentCode = $definition[6] ?? null;
                LedgerAccount::firstOrCreate(
                    ['school_id' => $school->id, 'code' => $code],
                    ['parent_id' => $parentCode ? LedgerAccount::where('school_id', $school->id)->where('code', $parentCode)->value('id') : null, 'name' => $name, 'account_class' => $class, 'subtype' => $subtype, 'normal_balance' => $normal, 'currency' => $currency, 'accepts_postings' => $posting, 'is_control_account' => in_array($code, ['1210', '2100', '2110', '2120', '2130'], true), 'is_system' => true, 'is_active' => true, 'archived_at' => null, 'created_by' => $userId],
                );
            }
            foreach (self::DEFAULT_MAPPINGS as $type => $code) {
                AccountMapping::firstOrCreate(['school_id' => $school->id, 'mapping_type' => $type, 'source_type' => null, 'source_id' => null], ['ledger_account_id' => LedgerAccount::where('school_id', $school->id)->where('code', $code)->value('id'), 'updated_by' => $userId]);
            }
            CostCentre::firstOrCreate(['school_id' => $school->id, 'code' => 'GENERAL'], ['name' => 'General School Operations', 'is_active' => true]);
            AccountingFund::firstOrCreate(['school_id' => $school->id, 'code' => 'GENERAL'], ['name' => 'Unrestricted General Fund', 'is_restricted' => false, 'is_active' => true]);
            $this->ensurePeriods($school);
            $cashCodes = ['cash' => '1110', 'petty_cash' => '1120', 'bank' => '1130', 'mobile_money' => '1150'];
            foreach (FinancialAccount::where('school_id', $school->id)->get() as $account) {
                if ($account->ledger_account_id) {
                    continue;
                }
                $code = $cashCodes[$account->type] ?? null;
                if ($code) {
                    $account->update(['ledger_account_id' => LedgerAccount::where('school_id', $school->id)->where('code', $code)->value('id')]);
                }
            }
            SchoolSetting::setValue($school->id, 'accounting_enabled', 'enabled');
            SchoolSetting::setValue($school->id, 'accounting_currency', $currency);
            if (! $wasEnabled && $userId) {
                AuditLog::record($school->id, 'accounting.activated', null, ['currency' => $currency]);
            }
        });
    }

    public function ensurePeriods(School $school, ?int $year = null): FiscalYear
    {
        $year ??= (int) now()->year;
        $fy = FiscalYear::where('school_id', $school->id)->whereDate('starts_on', "{$year}-01-01")->first()
            ?? FiscalYear::create(['school_id' => $school->id, 'starts_on' => "{$year}-01-01", 'name' => (string) $year, 'ends_on' => "{$year}-12-31", 'status' => 'open']);
        foreach (range(1, 12) as $month) {
            $start = CarbonImmutable::create($year, $month, 1);
            if (! AccountingPeriod::where('school_id', $school->id)->whereDate('starts_on', $start->toDateString())->exists()) {
                AccountingPeriod::create(['school_id' => $school->id, 'starts_on' => $start->toDateString(), 'fiscal_year_id' => $fy->id, 'name' => $start->format('F Y'), 'ends_on' => $start->endOfMonth()->toDateString(), 'status' => 'open']);
            }
        }

        return $fy;
    }
}
