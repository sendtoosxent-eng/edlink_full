<?php

namespace App\Livewire;

use App\Models\GraduationRecord;
use App\Models\Subject;
use App\Models\StudentSubjectSelection;
use App\Services\GraduationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Graduates extends Component
{
    use WithPagination;

    public string $search = '';
    public string $year = '';
    public string $reversalReason = '';
    public ?int $reversingId = null;
    public ?int $resultId = null;
    public string $externalResultType = '';
    public string $externalResult = '';
    public array $externalSubjects = [];
    public array $subjectResults = [];

    public function beginExternalResult(int $id): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $record = $this->record($id);
        $this->resultId = $record->id;
        $this->externalResultType = $this->resultTypeFor($record);
        $this->subjectResults = is_array(json_decode((string) $record->external_result, true)) ? json_decode((string) $record->external_result, true) : [];
        $this->externalSubjects = StudentSubjectSelection::with('subject')->where('student_id', $record->student_id)->where('term_id', $record->term_id)->get()->pluck('subject.name')->filter()->values()->all();
        if ($this->externalSubjects === []) {
            $this->externalSubjects = DB::table('staff_subjects')->join('subjects', 'subjects.id', '=', 'staff_subjects.subject_id')->where('staff_subjects.school_id', $record->school_id)->where('staff_subjects.term_id', $record->term_id)->where('staff_subjects.school_class_id', $record->school_class_id)->pluck('subjects.name')->unique()->values()->all();
        }
        foreach ($this->externalSubjects as $subject) $this->subjectResults[$subject] ??= '';
    }

    public function saveExternalResult(): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $this->validate(['resultId' => ['required', 'integer'], 'subjectResults' => ['required', 'array'], 'subjectResults.*' => ['nullable', 'string', 'max:100']]);
        $record = $this->record($this->resultId);
        $record->update(['external_result_type' => $this->resultTypeFor($record), 'external_result' => json_encode($this->subjectResults), 'external_result_entered_at' => now(), 'external_result_entered_by' => Auth::id()]);
        $this->reset(['resultId', 'externalResultType', 'externalResult', 'externalSubjects', 'subjectResults']);
        session()->flash('status', 'External result saved for '.$record->student->name.'.');
    }

    private function resultTypeFor(GraduationRecord $record): string
    {
        $class = strtolower((string) $record->schoolClass?->name);
        if ($record->school?->school_type === 'primary' || str_contains($class, 'primary')) return 'PLE';
        if (preg_match('/\bs ?4\b|\bsenior four\b/', $class)) return 'UCE';
        if (preg_match('/\bs ?6\b|\bsenior six\b/', $class)) return 'UACE';
        return 'Other';
    }

    public function beginReversal(int $id): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $this->record($id);
        $this->reversingId = $id;
        $this->reversalReason = '';
    }

    public function reverse(GraduationService $service): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $this->validate(['reversalReason' => ['required', 'string', 'min:8', 'max:255']]);
        $record = $this->record((int) $this->reversingId);
        $service->reverse($record, $this->reversalReason);
        $this->reset(['reversingId', 'reversalReason']);
        session()->flash('status', $record->student->name.' was restored to active learner status.');
    }

    public function render()
    {
        $schoolId = Auth::user()->school_id;
        $query = GraduationRecord::with(['student', 'schoolClass', 'term'])->where('school_id', $schoolId)->whereNull('reversed_at')
            ->when($this->year, fn ($q) => $q->where('graduation_year', $this->year))
            ->when($this->search, fn ($q) => $q->whereHas('student', fn ($student) => $student->where('name', 'like', '%'.$this->search.'%')->orWhere('admission_no', 'like', '%'.$this->search.'%')));

        return view('livewire.graduates', [
            'graduates' => $query->latest('graduated_at')->paginate(20),
            'years' => GraduationRecord::where('school_id', $schoolId)->distinct()->orderByDesc('graduation_year')->pluck('graduation_year'),
            'canManage' => Auth::user()->hasPermission('students.manage'),
            'pageTitle' => 'Graduates & Alumni',
        ]);
    }

    private function record(int $id): GraduationRecord
    {
        return GraduationRecord::where('school_id', Auth::user()->school_id)->whereNull('reversed_at')->findOrFail($id);
    }
}
