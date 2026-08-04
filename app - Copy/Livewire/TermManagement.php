<?php

namespace App\Livewire;

use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TermManagement extends Component
{
    public string $name = '';
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
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $hasOpenTerm = Term::where('school_id', $school->id)->where('is_current', true)->exists();

        $term = Term::create([
            'school_id' => $school->id,
            'name' => $this->name,
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

        $this->reset(['name']);
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
