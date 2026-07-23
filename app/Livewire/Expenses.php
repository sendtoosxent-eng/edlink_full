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
            $expense = Expense::create(['school_id' => Auth::user()->school_id, 'term_id' => $this->selectedTerm->id, 'category' => $this->category, 'amount' => $this->amount, 'description' => $this->description ?: null, 'expense_date' => $this->expense_date, 'reference_number' => $this->reference_number, 'recorded_by' => Auth::id()]);
            CashPoolEntry::create(['school_id' => $expense->school_id, 'term_id' => $expense->term_id, 'expense_id' => $expense->id, 'direction' => 'debit', 'amount' => $expense->amount, 'description' => '['.$expense->reference_number.'] '.$expense->category.($expense->description ? ': '.$expense->description : ''), 'transacted_at' => $expense->expense_date->startOfDay(), 'recorded_by' => Auth::id()]);
        });
        $this->reset(['amount', 'description', 'reference_number']);
        session()->flash('status', 'Expense recorded and deducted from the school cash pool.');
    }

    public function confirmDelete(int $id): void { $this->deletingId = $id; }
    public function cancelDelete(): void { $this->deletingId = null; }
    public function delete(int $id): void
    {
        abort_unless(Auth::user()->hasPermission('finance.expenses'), 403);
        if (! $this->canEdit) { return; }
        $expense = Expense::where('school_id', Auth::user()->school_id)->where('term_id', $this->selectedTerm->id)->findOrFail($id);
        DB::transaction(fn () => $expense->delete());
        $this->deletingId = null;
        session()->flash('status', 'Expense removed and the pool balance restored.');
    }

    public function render()
    {
        $terms = Term::where('school_id', Auth::user()->school_id)->orderByDesc('year')->orderByDesc('id')->get();
        $expenses = Expense::where('school_id', Auth::user()->school_id)->when($this->termId, fn ($query) => $query->where('term_id', $this->termId))->latest('expense_date')->paginate(15);
        return view('livewire.expenses', ['terms' => $terms, 'expenses' => $expenses, 'total' => (float) $expenses->getCollection()->sum('amount'), 'totalsByCategory' => Expense::where('school_id', Auth::user()->school_id)->when($this->termId, fn ($query) => $query->where('term_id', $this->termId))->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category'), 'pageTitle' => 'Expenses']);
    }
}
