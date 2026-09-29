<?php

namespace App\Livewire;

use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamCategoryGroup;
use App\Models\ExamCategoryGroupAssignment;
use App\Models\SchoolClass;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ExamCategories extends Component
{
    public array $groups = [];

    public string $termId = '';

    public function mount(): void
    {
        $this->authorizeManagement();
        $term = Term::currentFor(Auth::user()->school)
            ?? Term::where('school_id', Auth::user()->school_id)->orderByDesc('year')->orderByDesc('term_number')->first();
        $this->termId = (string) ($term?->id ?? '');
        $this->loadGroups();
    }

    public function updatedTermId(): void
    {
        $this->authorizeManagement();
        $this->loadGroups();
        $this->resetValidation();
    }

    public function addGroup(): void
    {
        $this->authorizeManagement();
        $this->groups[] = ['id' => null, 'name' => '', 'class_ids' => [], 'categories' => []];
    }

    public function removeGroup(int $index): void
    {
        $this->authorizeManagement();
        unset($this->groups[$index]);
        $this->groups = array_values($this->groups);
    }

    public function addCategory(int $groupIndex): void
    {
        $this->authorizeManagement();
        abort_unless(isset($this->groups[$groupIndex]), 404);
        $this->groups[$groupIndex]['categories'][] = ['id' => null, 'name' => '', 'weight' => ''];
    }

    public function removeCategory(int $groupIndex, int $categoryIndex): void
    {
        $this->authorizeManagement();
        abort_unless(isset($this->groups[$groupIndex]['categories'][$categoryIndex]), 404);
        unset($this->groups[$groupIndex]['categories'][$categoryIndex]);
        $this->groups[$groupIndex]['categories'] = array_values($this->groups[$groupIndex]['categories']);
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $schoolId = Auth::user()->school_id;
        $this->validate([
            'termId' => ['required', Rule::exists('terms', 'id')->where('school_id', $schoolId)],
            'groups' => ['array', 'max:20'],
            'groups.*.id' => ['nullable', Rule::exists('exam_category_groups', 'id')->where('school_id', $schoolId)->where('term_id', $this->termId)],
            'groups.*.name' => ['required', 'string', 'max:100'],
            'groups.*.class_ids' => ['array'],
            'groups.*.class_ids.*' => ['integer', 'distinct', Rule::exists('school_classes', 'id')->where('school_id', $schoolId)],
            'groups.*.categories' => ['required', 'array', 'min:1', 'max:12'],
            'groups.*.categories.*.id' => ['nullable', Rule::exists('exam_categories', 'id')->where('school_id', $schoolId)->where('term_id', $this->termId)],
            'groups.*.categories.*.name' => ['required', 'string', 'max:100'],
            'groups.*.categories.*.weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ]);

        $term = Term::where('school_id', $schoolId)->findOrFail($this->termId);
        if (! $term->isOpen()) {
            $this->addError('termId', 'Assessment category groups can only be changed while the term is open.');

            return;
        }

        $groupNames = collect($this->groups)->map(fn (array $group) => mb_strtolower(trim($group['name'])));
        if ($groupNames->duplicates()->isNotEmpty()) {
            $this->addError('groups', 'Every category group must have a unique name in this term.');

            return;
        }

        $assignedClasses = collect($this->groups)->flatMap(fn (array $group) => $group['class_ids'] ?? [])->map(fn ($id) => (int) $id);
        if ($assignedClasses->duplicates()->isNotEmpty()) {
            $this->addError('groups', 'A class can belong to only one examination category group per term.');

            return;
        }

        foreach ($this->groups as $groupIndex => $group) {
            $categoryNames = collect($group['categories'])->map(fn (array $category) => mb_strtolower(trim($category['name'])));
            if ($categoryNames->duplicates()->isNotEmpty()) {
                $this->addError("groups.$groupIndex.categories", 'Category names must be unique inside a group.');
            }
            $weight = collect($group['categories'])->sum(fn (array $category) => (float) $category['weight']);
            if (abs($weight - 100) > 0.01) {
                $this->addError("groups.$groupIndex.categories", 'Category percentages must add up to exactly 100%.');
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->guardClassAssignmentChanges($schoolId);
        $this->guardPublishedGroups($schoolId);

        DB::transaction(function () use ($schoolId): void {
            $keptGroupIds = [];
            $groupIdsByIndex = [];

            foreach (array_values($this->groups) as $groupIndex => $group) {
                $record = ExamCategoryGroup::updateOrCreate(
                    ['id' => $group['id'] ?? null, 'school_id' => $schoolId, 'term_id' => $this->termId],
                    ['name' => trim($group['name'])],
                );
                $keptGroupIds[] = $record->id;
                $groupIdsByIndex[$groupIndex] = $record->id;
                $keptCategoryIds = [];

                foreach (array_values($group['categories']) as $position => $category) {
                    $categoryRecord = ExamCategory::updateOrCreate(
                        ['id' => $category['id'] ?? null, 'school_id' => $schoolId, 'term_id' => $this->termId],
                        ['exam_category_group_id' => $record->id, 'name' => trim($category['name']), 'weight' => $category['weight'], 'sort_order' => $position],
                    );
                    $keptCategoryIds[] = $categoryRecord->id;
                }

                $removedCategories = ExamCategory::where('exam_category_group_id', $record->id)
                    ->when($keptCategoryIds !== [], fn ($query) => $query->whereNotIn('id', $keptCategoryIds))->get();
                foreach ($removedCategories as $category) {
                    if ($category->exams()->exists()) {
                        throw ValidationException::withMessages(['groups' => "{$category->name} is assigned to an exam and cannot be removed."]);
                    }
                    $category->delete();
                }
            }

            $removedGroups = ExamCategoryGroup::where('school_id', $schoolId)->where('term_id', $this->termId)
                ->when($keptGroupIds !== [], fn ($query) => $query->whereNotIn('id', $keptGroupIds))->with('categories')->get();
            foreach ($removedGroups as $group) {
                if (Exam::whereIn('exam_category_id', $group->categories->pluck('id'))->exists()) {
                    throw ValidationException::withMessages(['groups' => "{$group->name} is used by an exam and cannot be removed."]);
                }
                $group->delete();
            }

            ExamCategoryGroupAssignment::where('school_id', $schoolId)->where('term_id', $this->termId)->delete();
            foreach (array_values($this->groups) as $groupIndex => $group) {
                foreach ($group['class_ids'] ?? [] as $classId) {
                    ExamCategoryGroupAssignment::create([
                        'school_id' => $schoolId,
                        'term_id' => $this->termId,
                        'school_class_id' => $classId,
                        'exam_category_group_id' => $groupIdsByIndex[$groupIndex],
                    ]);
                }
            }
        });

        $this->loadGroups();
        session()->flash('status', 'Examination category groups, percentages, and class assignments saved.');
    }

    private function guardPublishedGroups(int $schoolId): void
    {
        $existing = ExamCategoryGroup::with(['categories', 'assignments'])->where('school_id', $schoolId)->where('term_id', $this->termId)->get()->keyBy('id');
        foreach ($this->groups as $index => $payload) {
            $group = ! empty($payload['id']) ? $existing->get((int) $payload['id']) : null;
            if (! $group) {
                continue;
            }
            $hasPublishedExam = Exam::where('school_id', $schoolId)->where('term_id', $this->termId)->whereNotNull('published_at')
                ->whereIn('exam_category_id', $group->categories->pluck('id'))->exists();
            if (! $hasPublishedExam) {
                continue;
            }

            $storedCategories = $group->categories->map(fn (ExamCategory $category) => [
                'id' => (int) $category->id,
                'name' => trim($category->name),
                'weight' => round((float) $category->weight, 2),
            ])->values()->all();
            $submittedCategories = collect($payload['categories'])->map(fn (array $category) => [
                'id' => (int) ($category['id'] ?? 0),
                'name' => trim($category['name']),
                'weight' => round((float) $category['weight'], 2),
            ])->values()->all();
            $storedClasses = $group->assignments->pluck('school_class_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $submittedClasses = collect($payload['class_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();

            if (trim($group->name) !== trim($payload['name']) || $storedCategories !== $submittedCategories || $storedClasses !== $submittedClasses) {
                throw ValidationException::withMessages([
                    "groups.$index.name" => 'This group is used by published results. Unpublish those exams before changing its name, percentages, categories, or classes.',
                ]);
            }
        }
    }

    private function guardClassAssignmentChanges(int $schoolId): void
    {
        $current = ExamCategoryGroupAssignment::where('school_id', $schoolId)->where('term_id', $this->termId)
            ->pluck('exam_category_group_id', 'school_class_id')->map(fn ($id) => (string) $id);
        $proposed = collect();
        foreach ($this->groups as $index => $group) {
            $groupKey = ! empty($group['id']) ? (string) $group['id'] : 'new:'.$index;
            foreach ($group['class_ids'] ?? [] as $classId) {
                $proposed->put((int) $classId, $groupKey);
            }
        }

        $changedClassIds = $current->keys()->merge($proposed->keys())->unique()->filter(
            fn ($classId) => ($current->get($classId) ?? null) !== ($proposed->get($classId) ?? null)
        );
        if ($changedClassIds->isEmpty()) {
            return;
        }

        $hasLockedExam = Exam::where('school_id', $schoolId)->where('term_id', $this->termId)->whereIn('school_class_id', $changedClassIds)
            ->where(function ($query) {
                $query->whereNotNull('published_at')->orWhereHas('papers', fn ($paper) => $paper->whereExists(fn ($marks) => $marks->selectRaw('1')->from('exam_marks')->whereColumn('exam_marks.exam_paper_id', 'exam_papers.id')));
            })->exists();
        if ($hasLockedExam) {
            throw ValidationException::withMessages([
                'groups' => 'A class assessment group cannot be changed after marks have been entered or results published. Reopen and clear the affected workflow first, or apply the new group in the next term.',
            ]);
        }
    }

    private function loadGroups(): void
    {
        $this->groups = ExamCategoryGroup::with(['categories', 'assignments'])
            ->where('school_id', Auth::user()->school_id)->where('term_id', $this->termId ?: 0)->orderBy('name')->get()
            ->map(fn (ExamCategoryGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'class_ids' => $group->assignments->pluck('school_class_id')->map(fn ($id) => (string) $id)->all(),
                'categories' => $group->categories->map(fn (ExamCategory $category) => [
                    'id' => $category->id, 'name' => $category->name, 'weight' => (string) $category->weight,
                ])->all(),
            ])->all();
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('settings.manage') || Auth::user()->hasPermission('exams.setup'), 403);
    }

    public function render()
    {
        return view('livewire.exam-categories', [
            'terms' => Term::where('school_id', Auth::user()->school_id)->orderByDesc('year')->orderByDesc('term_number')->orderByDesc('id')->get(),
            'classes' => SchoolClass::where('school_id', Auth::user()->school_id)->orderBy('sort_order')->orderBy('name')->get(),
            'selectedTerm' => Term::where('school_id', Auth::user()->school_id)->find($this->termId),
            'pageTitle' => 'Examination Category Groups',
        ]);
    }
}
