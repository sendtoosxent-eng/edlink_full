<?php

namespace App\Livewire;

use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Expenses extends Component
{
    use WithPagination;

    public string $termId = '';
    public string $category = 'Payroll';
    public string $amount = '';
    public string $description = '';
    public string $expense_date = '';
    public string $reference_number = '';
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->termId = (string) Auth::user()->school->currentTerm()?->id;
        $this->expense_date = now()->toDateString();
    }

    #[Computed]
    public function selectedTerm(): ?Term { return Term::where('school_id', Auth::user()->school_id)->find($this->termId); }
    #[Computed]
    public function canEdit(): bool { return $this->selectedTerm?->isOpen() ?? false; }

    public function add(): void
    {
        abort_unless(Auth::user()->hasPermission('finance.expenses'), 403);
        if (! $this->canEdit) { session()->flash('error', 'Expenses can only be recorded in an open term.'); return; }
        $this->reference_number = strtoupper(trim($this->reference_number));
        $this->validate(['category' => ['required', 'in:'.implode(',', Expense::CATEGORIES)], 'amount' => ['required', 'numeric', 'min:0.01'], 'description' => ['nullable', 'string', 'max:255'], 'expense_date' => ['required', 'date'], 'reference_number' => ['required', 'string', 'max:100', Rule::unique('expenses', 'reference_number')->where('school_id', Auth::user()->school_id)]]);

        DB::transaction(function () {
            $expense = Expense::create(['school_id' => Auth::user()->school_id, 'financial_account_id' => \App\Models\FinancialAccount::where('school_id', Auth::user()->school_id)->where('type','cash')->value('id'), 'term_id' => $this->selectedTerm->id, 'category' => $this->category, 'amount' => $this->amount, 'description' => $this->description ?: null, 'expense_date' => $this->expense_date, 'reference_number' => $this->reference_number, 'recorded_by' => Auth::id()]);
        });
        $this->reset(['amount', 'description', 'reference_number']);
        session()->flash('status', 'Expense recorded and sent for approval. It will be deducted from the cash pool after approval.');
    }

    public function confirmDelete(int $id): void { $this->deletingId = $id; }
    public function cancelDelete(): void { $this->deletingId = null; }
    public function delete(int $id): void
    {
        abort_unless(Auth::user()->hasPermission('finance.expenses'), 403);
        if (! $this->canEdit) { return; }
        $expense = Expense::where('school_id', Auth::user()->school_id)->where('term_id', $this->selectedTerm->id)->findOrFail($id);
        $ledger = \App\Models\FinanceLedgerEntry::where(['source_type' => \App\Models\Expense::class, 'source_id' => $expense->id])->first();
        if ($ledger && $ledger->status !== 'pending') { session()->flash('error', 'Posted expenses cannot be deleted. Reverse the transaction from Ledger & Reconciliation.'); $this->deletingId = null; return; }
        DB::transaction(fn () => $expense->delete());
        $this->deletingId = null;
        session()->flash('status', 'Pending expense deleted. It had not affected the cash pool.');
    }

    public function render()
    {
        $terms = Term::where('school_id', Auth::user()->school_id)->orderByDesc('year')->orderByDesc('id')->get();
        $expenseQuery = Expense::posted()->where('school_id', Auth::user()->school_id)
            ->when($this->termId, fn ($query) => $query->where('term_id', $this->termId));
        $expenses = (clone $expenseQuery)->latest('expense_date')->paginate(15);
        return view('livewire.expenses', [
            'terms' => $terms,
            'expenses' => $expenses,
            'total' => (float) (clone $expenseQuery)->sum('amount'),
            'totalsByCategory' => (clone $expenseQuery)->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category'),
            'pageTitle' => 'Expenses',
        ]);
    }
}
