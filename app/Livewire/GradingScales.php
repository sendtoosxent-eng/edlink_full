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

    public function mount(): void
    {
        abort_unless($this->canManage(), 403);
    }

    public function save(): void
    {
        $schoolId = Auth::user()->school_id;
        $this->grade = strtoupper(trim($this->grade));

        $validated = $this->validate([
            'minimum' => ['required', 'numeric', 'min:0', 'max:100'],
            'maximum' => ['required', 'numeric', 'min:0', 'max:100', 'gte:minimum'],
            'grade' => ['required', 'string', 'max:10', Rule::unique('grading_scales', 'grade')->where('school_id', $schoolId)->ignore($this->editingId)],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $overlaps = GradingScale::where('school_id', $schoolId)
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
        $scale = $this->schoolScales()->findOrFail($id);
        $this->editingId = $scale->id;
        $this->minimum = (string) (float) $scale->minimum_percentage;
        $this->maximum = (string) (float) $scale->maximum_percentage;
        $this->grade = $scale->grade;
        $this->remark = $scale->remark ?? '';
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->reset(['editingId', 'minimum', 'maximum', 'grade', 'remark']);
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        $this->schoolScales()->findOrFail($id)->delete();
        if ($this->editingId === $id) {
            $this->cancelEditing();
        }
        session()->flash('status', 'Grade band removed. Review percentage coverage before publishing results.');
    }

    public function installDefaults(): void
    {
        if ($this->schoolScales()->exists()) {
            $this->addError('grade', 'Remove existing bands before installing the default scale.');
            return;
        }

        $defaults = [
            ['A', 80, 100, 'Excellent'],
            ['B', 70, 79.99, 'Very good'],
            ['C', 60, 69.99, 'Good'],
            ['D', 50, 59.99, 'Satisfactory'],
            ['E', 40, 49.99, 'Needs improvement'],
            ['F', 0, 39.99, 'Below standard'],
        ];

        foreach ($defaults as [$grade, $minimum, $maximum, $remark]) {
            GradingScale::create(compact('grade', 'remark') + [
                'school_id' => Auth::user()->school_id,
                'minimum_percentage' => $minimum,
                'maximum_percentage' => $maximum,
            ]);
        }

        session()->flash('status', 'Default A-F grading scale installed. You can edit each band.');
    }

    protected function schoolScales(): Builder
    {
        return GradingScale::where('school_id', Auth::user()->school_id);
    }

    protected function canManage(): bool
    {
        return in_array(Auth::user()->role, ['admin', 'academic_admin'], true);
    }

    public function render()
    {
        $scales = $this->schoolScales()->orderByDesc('minimum_percentage')->get();
        $covered = round($scales->sum(fn (GradingScale $scale) => (float) $scale->maximum_percentage - (float) $scale->minimum_percentage + 0.01), 2);

        return view('livewire.grading-scales', [
            'scales' => $scales,
            'coverageComplete' => $scales->isNotEmpty() && $covered >= 100.01,
            'pageTitle' => 'Grading Scales',
        ]);
    }
}
