<?php

namespace App\Livewire;

use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TermManagement extends Component
{
    public string $termNumber = '1';
    public string $year = '';

    public ?int $closingTermId = null;

    public function mount(): void
    {
        $this->year = (string) now()->year;
    }

    public function add(): void
    {
        $school = Auth::user()->school;

        $this->validate([
            'termNumber' => ['required', 'integer', 'between:1,3'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $number = (int) $this->termNumber;
        if (Term::where('school_id', $school->id)->where('year', $this->year)->where('term_number', $number)->exists()) {
            $this->addError('termNumber', 'This school already has Term '.$number.' for '.$this->year.'.');
            return;
        }

        $existingNumbers = Term::where('school_id', $school->id)->where('year', $this->year)->pluck('term_number');
        if ($number > 1 && ! $existingNumbers->contains($number - 1)) {
            $this->addError('termNumber', 'Create Term '.($number - 1).' before Term '.$number.'.');
            return;
        }
        if ($number === 1) {
            $previousYear = (int) $this->year - 1;
            $previousCount = Term::where('school_id', $school->id)->where('year', $previousYear)->whereNotNull('term_number')->distinct()->count('term_number');
            if ($previousCount > 0 && $previousCount < 3) {
                $this->addError('year', 'Complete all three terms for '.$previousYear.' before starting '.$this->year.'.');
                return;
            }
        }

        $hasOpenTerm = Term::where('school_id', $school->id)->where('is_current', true)->exists();

        $term = Term::create([
            'school_id' => $school->id,
            'name' => 'Term '.$number,
            'term_number' => $number,
            'year' => $this->year,
            'is_current' => ! $hasOpenTerm,
            'status' => $hasOpenTerm ? 'pending' : 'open',
            'locked' => false,
        ]);

        if (! $hasOpenTerm) {
            // Attach any waiting arrears straight away since this term opened immediately.
            \App\Models\Arrears::where('school_id', $school->id)
                ->where('applied', false)
                ->update(['applied_term_id' => $term->id, 'applied' => true]);
        }

        $this->termNumber = (string) min(3, $number + 1);
        $this->year = (string) now()->year;

        session()->flash('status', 'Term "'.$term->name.'" added'.($hasOpenTerm ? ' (pending — open it when ready).' : ' and opened.'));
    }

    public function openTerm(int $id): void
    {
        $term = Term::where('school_id', Auth::user()->school_id)->findOrFail($id);

        try {
            $term->openTerm();
            session()->flash('status', $term->name.', '.$term->year.' is now open.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function confirmClose(int $id): void
    {
        $this->closingTermId = $id;
    }

    public function cancelClose(): void
    {
        $this->closingTermId = null;
    }

    public function closeWithRoll(int $id): void
    {
        $term = Term::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $term->closeTerm(true);
        $this->closingTermId = null;
        session()->flash('status', $term->name.' closed and arrears rolled forward. This term is now permanently locked.');
    }

    public function closeWithoutRoll(int $id): void
    {
        $term = Term::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $term->closeTerm(false);
        $this->closingTermId = null;
        session()->flash('status', $term->name.' closed. It remains editable.');
    }

    public function prepareEnrolments(int $targetTermId): void
    {
        $schoolId = Auth::user()->school_id;
        $targetTerm = Term::where('school_id', $schoolId)->findOrFail($targetTermId);
        $sourceTerm = Term::where('school_id', $schoolId)
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->first();

        if (! $sourceTerm) {
            session()->flash('error', 'Close a term before preparing enrolments for the next one.');
            return;
        }

        if (! $sourceTerm->canProgressTo($targetTerm)) {
            session()->flash('error', 'Terms must progress in order: Term 1 to Term 2, Term 2 to Term 3, then Term 3 to next year\'s Term 1.');
            return;
        }

        try {
            $count = $sourceTerm->prepareEnrolmentsFor($targetTerm);
            session()->flash('status', $count.' learner enrolment'.($count === 1 ? ' was' : 's were').' prepared for '.$targetTerm->name.'. Review class placements before activating the term.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.term-management', [
            'terms' => Term::where('school_id', Auth::user()->school_id)
                ->orderByDesc('year')
                ->orderByDesc('created_at')
                ->get(),
            'pageTitle' => 'Terms',
        ]);
    }
}
