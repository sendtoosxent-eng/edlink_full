<?php

namespace App\Livewire;

use App\Models\AccountingJournal;
use App\Models\AccountingPeriod;
use App\Models\AccountMapping;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\FeeStructure;
use App\Models\FinancialAccount;
use App\Models\LedgerAccount;
use App\Models\SchoolSetting;
use App\Services\AccountingReportService;
use App\Services\AccountingSetupService;
use App\Services\DoubleEntryService;
use App\Services\StudentReceivablesService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Accounting extends Component
{
    use WithPagination;

    public string $tab = 'dashboard';

    public string $from = '';

    public string $to = '';

    public string $search = '';

    public string $accountCode = '';

    public string $accountName = '';

    public string $accountClass = 'expense';

    public string $accountSubtype = '';

    public string $normalBalance = 'debit';

    public bool $acceptsPostings = true;

    public ?int $parentId = null;

    public ?int $editingAccountId = null;

    public bool $editingAccountIsSystem = false;

    public array $mappings = [];

    public array $financialMappings = [];

    public string $journalDate = '';

    public string $journalReference = '';

    public string $journalDescription = '';

    public array $journalLines = [];

    public string $actionReason = '';

    public ?int $selectedAccountId = null;

    public function mount(AccountingSetupService $setup): void
    {
        $this->authorizePermission('accounting.dashboard.view');
        $setup->activate(Auth::user()->school, Auth::id());
        $this->tab = in_array(request('tab'), ['dashboard', 'accounts', 'journals', 'reports', 'settings', 'periods'], true) ? request('tab') : 'dashboard';
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->toDateString();
        $this->journalDate = now()->toDateString();
        $this->journalLines = [$this->blankLine(), $this->blankLine()];
        $this->loadMappings();
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['dashboard', 'accounts', 'journals', 'reports', 'settings', 'periods'], true), 404);
        $this->tab = $tab;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function addJournalLine(): void
    {
        $this->journalLines[] = $this->blankLine();
    }

    public function removeJournalLine(int $index): void
    {
        if (count($this->journalLines) > 2) {
            array_splice($this->journalLines, $index, 1);
        }
    }

    public function saveJournal(DoubleEntryService $service): void
    {
        $this->authorizePermission('accounting.journals.create');
        $data = $this->validate([
            'journalDate' => 'required|date', 'journalReference' => 'nullable|string|max:100', 'journalDescription' => 'required|string|max:2000',
            'journalLines' => 'required|array|min:2', 'journalLines.*.ledger_account_id' => 'required|integer',
            'journalLines.*.description' => 'nullable|string|max:255', 'journalLines.*.debit' => 'nullable|decimal:0,2|min:0', 'journalLines.*.credit' => 'nullable|decimal:0,2|min:0',
        ]);
        $journal = $service->create([
            'school_id' => Auth::user()->school_id, 'journal_date' => $data['journalDate'], 'reference' => trim($data['journalReference']) ?: null,
            'description' => $data['journalDescription'], 'journal_type' => 'manual', 'currency' => $this->currency(),
        ], $data['journalLines'], Auth::id());
        $this->journalReference = $this->journalDescription = '';
        $this->journalLines = [$this->blankLine(), $this->blankLine()];
        session()->flash('status', "Draft journal {$journal->number} saved.");
    }

    public function generateFeeAssessments(StudentReceivablesService $service): void
    {
        $this->authorizePermission('accounting.opening_balances.manage');
        $term = Auth::user()->school->currentTerm();
        if (! $term) {
            $this->addError('assessment', 'Set a current academic term before generating fee assessments.');

            return;
        }
        $result = $service->generateAssessments(Auth::user()->school, $term, Auth::id());
        session()->flash('status', "{$result['created']} fee assessments were created and submitted; {$result['existing']} were already present.");
    }

    public function submitJournal(int $id, DoubleEntryService $service): void
    {
        $this->authorizePermission('accounting.journals.submit');
        $service->submit($this->journal($id), Auth::id());
        session()->flash('status', 'Journal submitted for independent approval.');
    }

    public function approveJournal(int $id, DoubleEntryService $service): void
    {
        $this->authorizePermission('accounting.journals.approve');
        $service->approve($this->journal($id), Auth::id());
        session()->flash('status', 'Journal approved. It is ready to post.');
    }

    public function postJournal(int $id, DoubleEntryService $service): void
    {
        $this->authorizePermission('accounting.journals.post');
        $service->post($this->journal($id), Auth::id());
        session()->flash('status', 'Journal posted to the general ledger.');
    }

    public function reverseJournal(int $id, DoubleEntryService $service): void
    {
        $this->authorizePermission('accounting.journals.reverse');
        $this->validate(['actionReason' => 'required|string|min:8|max:500']);
        $service->reverse($this->journal($id), $this->actionReason, Auth::id());
        $this->actionReason = '';
        session()->flash('status', 'A balanced reversal journal was posted.');
    }

    public function addAccount(): void
    {
        $this->authorizePermission('accounting.accounts.manage');
        $schoolId = Auth::user()->school_id;
        $data = $this->validate([
            'accountCode' => ['required', 'string', 'max:30', Rule::unique('ledger_accounts', 'code')->where('school_id', $schoolId)],
            'accountName' => 'required|string|max:255', 'accountClass' => 'required|in:asset,liability,equity,income,expense',
            'accountSubtype' => 'nullable|string|max:60', 'normalBalance' => 'required|in:debit,credit', 'acceptsPostings' => 'boolean',
            'parentId' => ['nullable', Rule::exists('ledger_accounts', 'id')->where('school_id', $schoolId)],
        ]);
        $account = LedgerAccount::create([
            'school_id' => $schoolId, 'parent_id' => $data['parentId'], 'code' => trim($data['accountCode']), 'name' => trim($data['accountName']),
            'account_class' => $data['accountClass'], 'subtype' => trim($data['accountSubtype']) ?: null, 'normal_balance' => $data['normalBalance'],
            'currency' => $this->currency(), 'accepts_postings' => $data['acceptsPostings'],
            'is_control_account' => false, 'is_system' => false, 'is_active' => true, 'created_by' => Auth::id(),
        ]);
        AuditLog::record($schoolId, 'accounting.account.created', $account, $account->toArray());
        $this->reset(['accountCode', 'accountName', 'accountSubtype', 'parentId']);
        session()->flash('status', 'Ledger account added.');
    }

    public function toggleAccount(int $id): void
    {
        $this->authorizePermission('accounting.accounts.manage');
        $account = LedgerAccount::where('school_id', Auth::user()->school_id)->findOrFail($id);
        if ($account->is_system && $account->is_active) {
            $this->addError('account', 'System control accounts cannot be archived. Remap them through a controlled configuration change.');

            return;
        }
        $old = $account->only(['is_active', 'archived_at']);
        $account->update(['is_active' => ! $account->is_active, 'archived_at' => $account->is_active ? now() : null]);
        AuditLog::record($account->school_id, 'accounting.account.status_changed', $account, ['old' => $old, 'new' => $account->only(['is_active', 'archived_at'])]);
    }

    public function editAccount(int $id): void
    {
        $this->authorizePermission('accounting.accounts.manage');
        $account = LedgerAccount::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $this->editingAccountId = $account->id;
        $this->editingAccountIsSystem = $account->is_system;
        $this->accountCode = $account->code;
        $this->accountName = $account->name;
        $this->accountClass = $account->account_class;
        $this->accountSubtype = $account->subtype ?? '';
        $this->normalBalance = $account->normal_balance;
        $this->acceptsPostings = $account->accepts_postings;
        $this->parentId = $account->parent_id;
    }

    public function saveAccount(): void
    {
        $this->authorizePermission('accounting.accounts.manage');
        $schoolId = Auth::user()->school_id;
        $account = LedgerAccount::where('school_id', $schoolId)->findOrFail($this->editingAccountId);
        $data = $this->validate(['accountCode' => ['required', 'string', 'max:30', Rule::unique('ledger_accounts', 'code')->where('school_id', $schoolId)->ignore($account->id)], 'accountName' => 'required|string|max:255', 'accountClass' => 'required|in:asset,liability,equity,income,expense', 'accountSubtype' => 'nullable|string|max:60', 'normalBalance' => 'required|in:debit,credit', 'acceptsPostings' => 'boolean', 'parentId' => ['nullable', Rule::exists('ledger_accounts', 'id')->where('school_id', $schoolId)]]);
        if (($account->is_system || $account->hasPostedActivity()) && ($data['accountClass'] !== $account->account_class || $data['normalBalance'] !== $account->normal_balance)) {
            throw ValidationException::withMessages(['accountClass' => 'The class and normal balance of a system or posted account cannot be changed.']);
        }
        if ((int) $data['parentId'] === $account->id) {
            throw ValidationException::withMessages(['parentId' => 'An account cannot be its own parent.']);
        }
        $old = $account->toArray();
        $account->update(['code' => trim($data['accountCode']), 'name' => trim($data['accountName']), 'account_class' => $data['accountClass'], 'subtype' => trim($data['accountSubtype']) ?: null, 'normal_balance' => $data['normalBalance'], 'accepts_postings' => $data['acceptsPostings'], 'parent_id' => $data['parentId']]);
        AuditLog::record($schoolId, 'accounting.account.updated', $account, ['old' => $old, 'new' => $account->fresh()->toArray()]);
        $this->cancelAccountEdit();
        session()->flash('status', 'Ledger account updated and audited.');
    }

    public function cancelAccountEdit(): void
    {
        $this->reset(['editingAccountId', 'editingAccountIsSystem', 'accountCode', 'accountName', 'accountSubtype', 'parentId']);
        $this->accountClass = 'expense';
        $this->normalBalance = 'debit';
        $this->acceptsPostings = true;
    }

    public function deleteAccount(): void
    {
        $this->authorizePermission('accounting.accounts.manage');
        $account = LedgerAccount::where('school_id', Auth::user()->school_id)->findOrFail($this->editingAccountId);
        $dependencies = [
            'subaccounts' => $account->children()->exists(),
            'journal entries' => $account->lines()->exists(),
            'posting rules' => DB::table('account_mappings')->where('ledger_account_id', $account->id)->exists(),
            'financial accounts' => DB::table('financial_accounts')->where('ledger_account_id', $account->id)->exists(),
            'budget lines' => DB::table('accounting_budget_lines')->where('ledger_account_id', $account->id)->exists(),
            'expenses' => DB::table('expenses')->where('expense_ledger_account_id', $account->id)->exists(),
        ];
        $usedBy = array_keys(array_filter($dependencies));
        if ($account->is_system || $usedBy !== []) {
            $reason = $account->is_system ? 'It is a protected system account.' : 'It is used by '.implode(', ', $usedBy).'.';
            throw ValidationException::withMessages(['account' => "This ledger cannot be deleted. {$reason} Archive it instead."]);
        }

        DB::transaction(function () use ($account): void {
            AuditLog::record($account->school_id, 'accounting.account.deleted', $account, ['deleted_account' => $account->toArray()]);
            $account->delete();
        });
        $this->cancelAccountEdit();
        session()->flash('status', 'Unused ledger account deleted.');
    }

    public function saveMappings(): void
    {
        $this->authorizePermission('accounting.mappings.manage');
        $schoolId = Auth::user()->school_id;
        DB::transaction(function () use ($schoolId) {
            foreach ($this->mappings as $type => $accountId) {
                if (! $accountId) {
                    throw ValidationException::withMessages(["mappings.{$type}" => 'Select a ledger account for every posting rule.']);
                }
                $expectedClass = $this->mappingAccountClass($type);
                $account = LedgerAccount::where('school_id', $schoolId)->where('is_active', true)->where('accepts_postings', true)
                    ->when($expectedClass, fn ($query) => $query->where('account_class', $expectedClass))->findOrFail($accountId);
                $mapping = AccountMapping::where('school_id', $schoolId)->where('mapping_type', $type)->whereNull('source_type')->whereNull('source_id')->first();
                $old = $mapping?->ledger_account_id;
                $mapping = AccountMapping::updateOrCreate(['school_id' => $schoolId, 'mapping_type' => $type, 'source_type' => null, 'source_id' => null], ['ledger_account_id' => $account->id, 'updated_by' => Auth::id()]);
                if ((int) $old !== $account->id) {
                    AuditLog::record($schoolId, 'accounting.mapping.changed', $mapping, ['old_account_id' => $old, 'new_account_id' => $account->id, 'mapping_type' => $type]);
                }
            }
            $selected = array_values(array_filter($this->financialMappings));
            if (count($selected) !== count(array_unique($selected))) {
                throw ValidationException::withMessages(['financialMappings' => 'Each operational cash, bank, or mobile-money account must map to its own ledger posting account.']);
            }
            foreach ($this->financialMappings as $financialId => $ledgerId) {
                if (! $ledgerId) {
                    throw ValidationException::withMessages(["financialMappings.{$financialId}" => 'Every active financial account requires a ledger mapping before transactions can post.']);
                }
                $financial = FinancialAccount::where('school_id', $schoolId)->findOrFail($financialId);
                $account = LedgerAccount::where('school_id', $schoolId)->where('account_class', 'asset')->where('currency', $financial->currency)->where('is_active', true)->where('accepts_postings', true)->findOrFail($ledgerId);
                $old = $financial->ledger_account_id;
                $financial->update(['ledger_account_id' => $account->id]);
                if ((int) $old !== $account->id) {
                    AuditLog::record($schoolId, 'accounting.financial_account.remapped', $financial, ['old_account_id' => $old, 'new_account_id' => $account->id]);
                }
            }
        });
        session()->flash('status', 'Posting rules saved and audited.');
    }

    public function changePeriodStatus(int $id, string $status): void
    {
        $this->authorizePermission($status === 'open' ? 'accounting.periods.reopen' : 'accounting.periods.manage');
        abort_unless(in_array($status, ['open', 'soft_closed', 'locked'], true), 422);
        if ($status === 'open') {
            $this->validate(['actionReason' => 'required|string|min:8|max:500']);
        }
        $period = AccountingPeriod::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $old = $period->status;
        $period->update(['status' => $status, 'status_reason' => $this->actionReason ?: null, 'status_changed_by' => Auth::id(), 'status_changed_at' => now()]);
        AuditLog::record($period->school_id, 'accounting.period.status_changed', $period, ['old' => $old, 'new' => $status, 'reason' => $this->actionReason]);
        $this->actionReason = '';
    }

    private function loadMappings(): void
    {
        $this->mappings = AccountMapping::where('school_id', Auth::user()->school_id)->whereNull('source_type')->pluck('ledger_account_id', 'mapping_type')->map(fn ($id) => (string) $id)->all();
        foreach (Expense::CATEGORIES as $category) {
            $this->mappings['expense_category:'.str($category)->slug()] ??= $this->mappings['default_expense'] ?? '';
        }
        $termId = Auth::user()->school->currentTerm()?->id;
        if ($termId) {
            foreach (FeeStructure::where('school_id', Auth::user()->school_id)->where('term_id', $termId)->pluck('id') as $id) {
                $this->mappings['fee_structure:'.$id] ??= $this->mappings['default_fee_income'] ?? '';
            }
        }
        $this->financialMappings = FinancialAccount::where('school_id', Auth::user()->school_id)->pluck('ledger_account_id', 'id')->map(fn ($id) => (string) $id)->all();
    }

    private function mappingAccountClass(string $type): ?string
    {
        if (str_starts_with($type, 'expense_category:')) return 'expense';
        if (str_starts_with($type, 'fee_structure:')) return 'income';

        return match ($type) {
            'student_receivable', 'staff_advance' => 'asset',
            'fees_received_in_advance', 'supplier_payable', 'salaries_payable', 'statutory_deductions_payable' => 'liability',
            'default_fee_income', 'fee_discount' => 'income',
            'scholarship', 'bad_debt', 'default_expense', 'teaching_salary_expense', 'non_teaching_salary_expense', 'staff_benefits_expense', 'bank_charges', 'rounding_differences' => 'expense',
            'opening_balance', 'retained_surplus' => 'equity',
            default => null,
        };
    }

    private function journal(int $id): AccountingJournal
    {
        return AccountingJournal::where('school_id', Auth::user()->school_id)->findOrFail($id);
    }

    private function blankLine(): array
    {
        return ['ledger_account_id' => '', 'description' => '', 'debit' => '', 'credit' => ''];
    }

    private function currency(): string
    {
        return strtoupper((string) SchoolSetting::getValue(Auth::user()->school_id, 'accounting_currency', SchoolSetting::getValue(Auth::user()->school_id, 'currency', 'UGX')));
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(Auth::user()->hasPermission($permission), 403);
    }

    public function render(AccountingReportService $reports)
    {
        $schoolId = Auth::user()->school_id;
        $filters = ['from' => $this->from, 'to' => $this->to];
        $trial = $reports->trialBalance($schoolId, $filters);
        $summary = $reports->summary($schoolId, $filters);
        $search = trim($this->search);
        $accounts = LedgerAccount::where('school_id', $schoolId)->with('parent')
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('subtype', 'like', "%{$search}%")))
            ->orderBy('code')->get();
        $journals = AccountingJournal::where('school_id', $schoolId)->with(['lines.account'])
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match->where('number', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")->orWhere('journal_type', 'like', "%{$search}%")->orWhere('status', 'like', "%{$search}%")))
            ->latest('journal_date')->latest('id')->paginate(15);

        return view('livewire.accounting', [
            'accounts' => $accounts,
            'postingAccounts' => LedgerAccount::where('school_id', $schoolId)->where('is_active', true)->where('accepts_postings', true)->orderBy('code')->get(),
            'financialAccounts' => FinancialAccount::where('school_id', $schoolId)->orderBy('name')->get(),
            'feeStructures' => ($term = Auth::user()->school->currentTerm()) ? FeeStructure::with(['schoolClass', 'studentCategory'])->where('school_id', $schoolId)->where('term_id', $term->id)->orderBy('school_class_id')->get() : collect(),
            'periods' => AccountingPeriod::where('school_id', $schoolId)->latest('starts_on')->limit(24)->get(),
            'journals' => $journals,
            'trialBalance' => $trial, 'summary' => $summary, 'receivables' => $reports->receivablesByStudent($schoolId, $filters),
            'recent' => AccountingJournal::where('school_id', $schoolId)->where('status', 'posted')->latest('posted_at')->limit(8)->get(),
            'pageTitle' => 'Accounting', 'currency' => $this->currency(),
        ]);
    }
}
