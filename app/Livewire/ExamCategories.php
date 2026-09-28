<?php

namespace App\Livewire;

use App\Models\ExamCategory;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ExamCategories extends Component
{
    public array $categories = [];
    public string $termId = '';

    public function mount(): void
    {
        $this->authorizeManagement();
        $term = Term::currentFor(Auth::user()->school)
            ?? Term::where('school_id', Auth::user()->school_id)->orderByDesc('year')->orderByDesc('term_number')->first();
        $this->termId = (string) ($term?->id ?? '');
        $this->loadCategories();
    }

    public function updatedTermId(): void
    {
        $this->authorizeManagement();
        $this->loadCategories();
        $this->resetValidation();
    }

    public function addCategory(): void
    {
        $this->authorizeManagement();
        $this->categories[] = ['id' => null, 'name' => '', 'weight' => ''];
    }

    public function removeCategory(int $index): void
    {
        $this->authorizeManagement();
        unset($this->categories[$index]);
        $this->categories = array_values($this->categories);
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $schoolId = Auth::user()->school_id;
        $this->validate(['termId' => ['required', Rule::exists('terms', 'id')->where('school_id', $schoolId)]]);
        $rules = ['categories' => ['array', 'max:12']];
        foreach ($this->categories as $index => $category) {
            $rules["categories.$index.id"] = ['nullable', Rule::exists('exam_categories', 'id')->where('school_id', $schoolId)->where('term_id', $this->termId)];
            $nameRule = Rule::unique('exam_categories', 'name')->where('school_id', $schoolId)->where('term_id', $this->termId);
            if (! empty($category['id'])) {
                $nameRule->ignore($category['id']);
            }
            $rules["categories.$index.name"] = ['required', 'string', 'max:100', 'distinct:ignore_case', $nameRule];
            $rules["categories.$index.weight"] = ['required', 'numeric', 'min:0.01', 'max:100'];
        }
        $this->validate($rules);

        if ($this->categories !== [] && abs(collect($this->categories)->sum(fn (array $category) => (float) $category['weight']) - 100) > 0.01) {
            $this->addError('categories', 'Category percentages must add up to exactly 100%.');

            return;
        }

        DB::transaction(function () use ($schoolId): void {
            $keptIds = [];
            foreach (array_values($this->categories) as $position => $category) {
                $record = ExamCategory::updateOrCreate(
                    ['id' => $category['id'] ?? null, 'school_id' => $schoolId, 'term_id' => $this->termId],
                    ['name' => trim($category['name']), 'weight' => $category['weight'], 'sort_order' => $position],
                );
                $keptIds[] = $record->id;
            }

            $removed = ExamCategory::where('school_id', $schoolId)->where('term_id', $this->termId)->whereNotIn('id', $keptIds)->get();
            foreach ($removed as $category) {
                if ($category->exams()->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'categories' => "{$category->name} is assigned to an exam and cannot be removed.",
                    ]);
                }
                $category->delete();
            }
        });

        $this->loadCategories();
        session()->flash('status', 'Examination categories and weights saved.');
    }

    private function loadCategories(): void
    {
        $this->categories = ExamCategory::where('school_id', Auth::user()->school_id)->where('term_id', $this->termId ?: 0)
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (ExamCategory $category) => ['id' => $category->id, 'name' => $category->name, 'weight' => (string) $category->weight])
            ->all();
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('settings.manage'), 403);
    }

    public function render()
    {
        return view('livewire.exam-categories', [
            'terms' => Term::where('school_id', Auth::user()->school_id)->orderByDesc('year')->orderByDesc('term_number')->orderByDesc('id')->get(),
            'pageTitle' => 'Examination Categories',
        ]);
    }
}
