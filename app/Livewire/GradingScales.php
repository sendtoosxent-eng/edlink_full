<?php

namespace App\Livewire;

use App\Models\GradingScale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class GradingScales extends Component
{
    public ?int $editingId = null;
    public string $minimum = '';
    public string $maximum = '';
    public string $grade = '';
    public string $remark = '';
    public string $stage = '';
    public string $points = '';

    public function mount(): void
    {
        abort_unless($this->canManage(), 403);
        $this->stage = \App\Services\SchoolAcademicSetup::stagesFor(Auth::user()->school)[0];
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $schoolId = Auth::user()->school_id;
        $this->grade = strtoupper(trim($this->grade));

        $validated = $this->validate([
            'minimum' => ['required', 'numeric', 'min:0', 'max:100'],
            'maximum' => ['required', 'numeric', 'min:0', 'max:100', 'gte:minimum'],
            'grade' => ['required', 'string', 'max:10', Rule::unique('grading_scales', 'grade')->where(fn ($query) => $query->where('school_id', $schoolId)->where('education_stage', $this->stage))->ignore($this->editingId)],
            'points' => ['nullable', 'integer', 'min:0', 'max:20'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $overlaps = GradingScale::where('school_id', $schoolId)
            ->where('education_stage', $this->stage)
            ->when($this->editingId, fn (Builder $query) => $query->whereKeyNot($this->editingId))
            ->where('minimum_percentage', '<=', (float) $validated['maximum'])
            ->where('maximum_percentage', '>=', (float) $validated['minimum'])
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'minimum' => 'This range overlaps an existing grade band.',
                'maximum' => 'Adjust the range so every percentage has only one grade.',
            ]);
        }

        GradingScale::updateOrCreate(
            ['id' => $this->editingId, 'school_id' => $schoolId],
            [
                'minimum_percentage' => $validated['minimum'],
                'education_stage' => $this->stage,
                'aggregate_points' => $validated['points'] !== '' ? $validated['points'] : null,
                'maximum_percentage' => $validated['maximum'],
                'grade' => $validated['grade'],
                'remark' => trim($validated['remark'] ?? '') ?: null,
            ],
        );

        $message = $this->editingId ? 'Grade band updated.' : 'Grade band added.';
        $this->cancelEditing();
        session()->flash('status', $message);
    }

    public function edit(int $id): void
    {
        $this->authorizeManagement();
        $scale = $this->schoolScales()->findOrFail($id);
        $this->editingId = $scale->id;
        $this->minimum = (string) (float) $scale->minimum_percentage;
        $this->maximum = (string) (float) $scale->maximum_percentage;
        $this->grade = $scale->grade;
        $this->remark = $scale->remark ?? '';
        $this->points = (string) ($scale->aggregate_points ?? '');
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->reset(['editingId', 'minimum', 'maximum', 'grade', 'remark', 'points']);
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        $this->authorizeManagement();
        $this->schoolScales()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->cancelEditing();
        }
        session()->flash('status', 'Grade band removed. Review percentage coverage before publishing results.');
    }

    public function installDefaults(): void
    {
        $this->authorizeManagement();
        if ($this->schoolScales()->exists()) {
            $this->addError('grade', 'Remove existing bands before installing the default scale.');
            return;
        }

        \App\Services\SchoolAcademicSetup::installDefaultScale(Auth::user()->school, $this->stage);
        session()->flash('status', 'Default scale installed for this education stage. You can edit each band.');
    }

    protected function schoolScales(): Builder
    {
        return GradingScale::where('school_id', Auth::user()->school_id)->where('education_stage', $this->stage);
    }

    protected function canManage(): bool
    {
        return Auth::user()->hasPermission('exams.setup');
    }

    private function authorizeManagement(): void
    {
        abort_unless($this->canManage(), 403);
    }

    public function render()
    {
        $scales = $this->schoolScales()->orderByDesc('minimum_percentage')->get();
        $covered = round($scales->sum(fn (GradingScale $scale) => (float) $scale->maximum_percentage - (float) $scale->minimum_percentage + 0.01), 2);

        return view('livewire.grading-scales', [
            'scales' => $scales,
            'coverageComplete' => $scales->isNotEmpty() && $covered >= 100.01,
            'stages' => \App\Services\SchoolAcademicSetup::stagesFor(Auth::user()->school),
            'pageTitle' => 'Grading Scales',
        ]);
    }
}
