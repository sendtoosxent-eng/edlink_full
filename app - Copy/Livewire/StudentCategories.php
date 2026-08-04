<?php

namespace App\Livewire;

use App\Models\StudentCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StudentCategories extends Component
{
    public string $name = '';
    public ?int $deletingId = null;

    public function add(): void
    {
        $school = Auth::user()->school;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $exists = StudentCategory::where('school_id', $school->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($this->name)])
            ->exists();

        if ($exists) {
            $this->addError('name', 'This category already exists.');
            return;
        }

        StudentCategory::create([
            'school_id' => $school->id,
            'name' => $this->name,
        ]);

        $this->reset('name');
        session()->flash('status', 'Category added.');
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
        $category = StudentCategory::where('school_id', Auth::user()->school_id)->findOrFail($id);

        if ($category->students()->exists()) {
            session()->flash('error', 'Can\'t delete "'.$category->name.'" — students are already assigned to it.');
            $this->deletingId = null;
            return;
        }

        $category->delete();
        $this->deletingId = null;
        session()->flash('status', 'Category deleted.');
    }

    public function render()
    {
        return view('livewire.student-categories', [
            'categories' => StudentCategory::where('school_id', Auth::user()->school_id)
                ->withCount('students')
                ->orderBy('name')
                ->get(),
            'pageTitle' => 'Student Categories',
        ]);
    }
}
