<?php

namespace App\Livewire;

use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\StudentCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FeeStructures extends Component
{
    public ?int $school_class_id = null;
    public ?int $student_category_id = null;
    public string $amount = '';
    public ?int $deletingId = null;

    public function add(): void
    {
        $this->authorizeManagement();
        $school = Auth::user()->school;
        $term = $school->currentTerm();

        $this->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $school->id)],
            'student_category_id' => ['required', Rule::exists('student_categories', 'id')->where('school_id', $school->id)],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        if (! $term) {
            session()->flash('error', 'This school has no active term — can\'t create a fee structure without one.');
            return;
        }

        $exists = FeeStructure::where('school_class_id', $this->school_class_id)
            ->where('student_category_id', $this->student_category_id)
            ->where('term_id', $term->id)
            ->exists();

        if ($exists) {
            $this->addError('amount', 'A fee amount for this class + category already exists this term. Delete it first to change it.');
            return;
        }

        FeeStructure::create([
            'school_id' => $school->id,
            'school_class_id' => $this->school_class_id,
            'student_category_id' => $this->student_category_id,
            'term_id' => $term->id,
            'amount' => $this->amount,
        ]);

        $this->reset(['school_class_id', 'student_category_id', 'amount']);
        session()->flash('status', 'Fee structure added.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(int $id): void
    {
        $this->authorizeManagement();
        FeeStructure::where('school_id', Auth::user()->school_id)->findOrFail($id)->delete();
        $this->deletingId = null;
        session()->flash('status', 'Fee structure removed.');
    }

    public function render()
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();

        return view('livewire.fee-structures', [
            'classes' => SchoolClass::where('school_id', $school->id)->orderBy('name')->get(),
            'categories' => StudentCategory::where('school_id', $school->id)->orderBy('name')->get(),
            'term' => $term,
            'structures' => $term
                ? FeeStructure::with(['schoolClass', 'studentCategory'])
                    ->where('school_id', $school->id)
                    ->where('term_id', $term->id)
                    ->get()
                : collect(),
            'pageTitle' => 'Fee Structure',
        ]);
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('finance.ledger'), 403);
    }
}
