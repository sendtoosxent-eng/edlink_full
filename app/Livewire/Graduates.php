<?php

namespace App\Livewire;

use App\Models\GraduationRecord;
use App\Services\GraduationService;
use Illuminate\Support\Facades\Auth;
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
