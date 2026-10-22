<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\GraduationRecord;
use App\Models\Subject;
use App\Models\StudentSubjectSelection;
use App\Services\GraduationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
    public array $resultSummary = [];
    public array $subjectResults = [];

    public function beginExternalResult(int $id): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $record = $this->record($id);
        $this->resetValidation();
        $this->resultId = $record->id;
        $this->externalResultType = $record->external_result_type ?: $this->resultTypeFor($record);
        $saved = $record->externalResults();
        $this->resultSummary = array_merge([
            'exam_year' => (string) $record->graduation_year, 'index_number' => '',
            'total_aggregate' => '', 'total_points' => '', 'division' => '', 'overall_result' => '', 'remarks' => '',
        ], array_intersect_key($saved, array_flip(['exam_year', 'index_number', 'total_aggregate', 'total_points', 'division', 'overall_result', 'remarks'])));
        $subjects = Subject::where('school_id', $record->school_id)->get()->keyBy('name');
        $this->subjectResults = collect($saved['subjects'] ?? [])->map(fn ($row) => [
            'subject_id' => (string) ($row['subject_id'] ?? $subjects->get($row['subject'])?->id ?? ''),
            'mark' => (string) ($row['mark'] ?? ''), 'grade' => (string) ($row['grade'] ?? ''), 'aggregate' => (string) ($row['aggregate'] ?? ''),
        ])->all();
        if ($this->subjectResults === []) {
            $ids = StudentSubjectSelection::where('student_id', $record->student_id)->where('term_id', $record->term_id)->pluck('subject_id');
            if ($ids->isEmpty()) {
                $ids = DB::table('class_subjects')->where('school_id', $record->school_id)->where('term_id', $record->term_id)->where('school_class_id', $record->school_class_id)->pluck('subject_id');
            }
            $this->subjectResults = $ids->unique()->map(fn ($id) => ['subject_id' => (string) $id, 'mark' => '', 'grade' => '', 'aggregate' => ''])->values()->all();
        }
        if ($this->subjectResults === []) $this->addResultSubject();
    }

    public function addResultSubject(): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        if (count($this->subjectResults) < 30) {
            $this->subjectResults[] = ['subject_id' => '', 'mark' => '', 'grade' => '', 'aggregate' => ''];
        }
    }

    public function removeResultSubject(int $index): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        unset($this->subjectResults[$index]);
        $this->subjectResults = array_values($this->subjectResults);
        $this->resetValidation();
    }

    public function saveExternalResult(): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $this->validate([
            'resultId' => ['required', 'integer'], 'externalResultType' => ['required', Rule::in(['PLE', 'UCE', 'UACE', 'Other'])],
            'subjectResults' => ['required', 'array', 'min:1', 'max:30'],
            'subjectResults.*.subject_id' => ['required', 'integer', 'distinct', Rule::exists('subjects', 'id')->where('school_id', Auth::user()->school_id)],
            'subjectResults.*.mark' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'subjectResults.*.grade' => ['nullable', 'string', 'max:20'],
            'subjectResults.*.aggregate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'resultSummary.exam_year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'resultSummary.index_number' => ['nullable', 'string', 'max:100'],
            'resultSummary.total_aggregate' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'resultSummary.total_points' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'resultSummary.division' => ['nullable', 'string', 'max:50'],
            'resultSummary.overall_result' => ['nullable', 'string', 'max:150'],
            'resultSummary.remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        foreach ($this->subjectResults as $index => $row) {
            if (trim((string) ($row['mark'] ?? '')) === '' && trim((string) ($row['grade'] ?? '')) === '') {
                throw ValidationException::withMessages(["subjectResults.$index.grade" => 'Enter a mark or grade for this subject.']);
            }
        }
        $record = $this->record($this->resultId);
        $subjects = Subject::where('school_id', $record->school_id)->pluck('name', 'id');
        $nullable = fn ($value) => trim((string) $value) === '' ? null : trim((string) $value);
        $rows = collect($this->subjectResults)->map(fn ($row) => [
            'subject_id' => (int) $row['subject_id'], 'subject' => $subjects[$row['subject_id']],
            'mark' => $nullable($row['mark'] ?? null), 'grade' => $nullable($row['grade'] ?? null), 'aggregate' => $nullable($row['aggregate'] ?? null),
        ])->all();
        $payload = ['version' => 2, 'subjects' => $rows];
        foreach (['exam_year', 'index_number', 'total_aggregate', 'total_points', 'division', 'overall_result', 'remarks'] as $key) {
            $payload[$key] = $nullable($this->resultSummary[$key] ?? null);
        }
        $record->update(['external_result_type' => $this->externalResultType, 'external_result' => json_encode($payload, JSON_THROW_ON_ERROR), 'external_result_entered_at' => now(), 'external_result_entered_by' => Auth::id()]);
        AuditLog::record($record->school_id, 'graduate.external_results.saved', $record, ['exam_type' => $this->externalResultType, 'subject_count' => count($rows)]);
        $this->reset(['resultId', 'externalResultType', 'resultSummary', 'subjectResults']);
        session()->flash('status', 'External results saved for '.$record->student->name.'. They will appear on the recommendation letter.');
    }

    private function resultTypeFor(GraduationRecord $record): string
    {
        $class = strtolower((string) $record->schoolClass?->name);
        if (preg_match('/\bs ?4\b|\bsenior (four|4)\b/', $class)) return 'UCE';
        if (preg_match('/\bs ?6\b|\bsenior (six|6)\b/', $class)) return 'UACE';
        if ($record->school?->school_type === 'primary' || str_contains($class, 'primary')) return 'PLE';
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
            'resultStudent' => $this->resultId ? $this->record($this->resultId)->student : null,
            'subjects' => Subject::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'canManage' => Auth::user()->hasPermission('students.manage'),
            'pageTitle' => 'Graduates & Alumni',
        ]);
    }

    private function record(int $id): GraduationRecord
    {
        return GraduationRecord::where('school_id', Auth::user()->school_id)->whereNull('reversed_at')->findOrFail($id);
    }
}
