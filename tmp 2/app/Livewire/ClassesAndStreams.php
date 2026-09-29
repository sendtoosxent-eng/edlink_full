<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ClassesAndStreams extends Component
{
    public string $class_name = '';
    public string $education_stage = '';
    public string $school_class_id = '';
    public string $stream_name = '';
    public ?int $deletingClassId = null;
    public ?int $deletingStreamId = null;
    public ?int $editingClassId = null;
    public string $editingClassName = '';
    public ?int $editingStreamId = null;
    public string $editingStreamName = '';
    public array $classTeachers = [];

    public function assignClassTeacher(int $classId, string $teacherId): void
    {
        $class = SchoolClass::where('school_id', Auth::user()->school_id)->findOrFail($classId);
        $teacher = $teacherId === '' ? null : User::where('school_id', Auth::user()->school_id)->where('role', 'teacher')->where('employment_status', 'active')->findOrFail($teacherId);
        $class->update(['class_teacher_user_id' => $teacher?->id]);
        session()->flash('status', $teacher ? $teacher->name.' assigned as class teacher for '.$class->name.'.' : 'Class teacher assignment removed.');
    }

    public function addClass(): void
    {
        $school = Auth::user()->school;
        $schoolId = $school->id;
        $stages = \App\Services\SchoolAcademicSetup::stagesFor($school);
        $this->education_stage = $this->education_stage ?: $stages[0];
        $this->validate(['class_name' => ['required', 'string', 'max:255', Rule::unique('school_classes', 'name')->where('school_id', $schoolId)]]);
        $this->validate(['education_stage' => ['required', Rule::in($stages)]]);
        SchoolClass::create(['school_id' => $schoolId, 'name' => $this->class_name, 'education_stage' => $this->education_stage]);
        $this->reset('class_name'); session()->flash('status', 'Class created.');
    }

    public function addStream(): void
    {
        $schoolId = Auth::user()->school_id;
        $this->validate(['school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $schoolId)], 'stream_name' => ['required', 'string', 'max:255']]);
        Stream::create(['school_id' => $schoolId, 'school_class_id' => $this->school_class_id, 'name' => $this->stream_name]);
        $this->reset('stream_name'); session()->flash('status', 'Stream attached to class.');
    }

    public function startEditClass(int $id): void { $class = SchoolClass::where('school_id', Auth::user()->school_id)->findOrFail($id); $this->editingClassId = $id; $this->editingClassName = $class->name; }
    public function saveClass(): void
    {
        $class = SchoolClass::where('school_id', Auth::user()->school_id)->findOrFail($this->editingClassId);
        if ($class->is_system) { session()->flash('error', 'Seeded standard classes cannot be renamed.'); return; }
        $this->validate(['editingClassName' => ['required', 'string', 'max:255', Rule::unique('school_classes', 'name')->where('school_id', $class->school_id)->ignore($class->id)]]);
        $class->update(['name' => $this->editingClassName]); $this->editingClassId = null; session()->flash('status', 'Class updated.');
    }
    public function startEditStream(int $id): void { $stream = Stream::where('school_id', Auth::user()->school_id)->findOrFail($id); $this->editingStreamId = $id; $this->editingStreamName = $stream->name; }
    public function saveStream(): void { $stream = Stream::where('school_id', Auth::user()->school_id)->findOrFail($this->editingStreamId); $this->validate(['editingStreamName' => ['required', 'string', 'max:255']]); $stream->update(['name' => $this->editingStreamName]); $this->editingStreamId = null; session()->flash('status', 'Stream updated.'); }

    public function confirmDeleteClass(int $id): void { $this->deletingClassId = $id; $this->deletingStreamId = null; }
    public function confirmDeleteStream(int $id): void { $this->deletingStreamId = $id; $this->deletingClassId = null; }
    public function cancelDelete(): void { $this->deletingClassId = $this->deletingStreamId = null; }
    public function deleteClass(int $id): void
    {
        $class = SchoolClass::where('school_id', Auth::user()->school_id)->findOrFail($id);
        if ($class->is_system) { session()->flash('error', 'Seeded standard classes cannot be deleted.'); return; }
        if ($class->students()->exists() || $class->enrolments()->exists()) { session()->flash('error', 'This class has learner records and cannot be deleted.'); return; }
        $class->delete(); $this->deletingClassId = null; session()->flash('status', 'Class deleted.');
    }
    public function deleteStream(int $id): void
    {
        $stream = Stream::where('school_id', Auth::user()->school_id)->findOrFail($id);
        if ($stream->students()->exists() || $stream->enrolments()->exists()) { session()->flash('error', 'This stream has learner records and cannot be deleted.'); return; }
        $stream->delete(); $this->deletingStreamId = null; session()->flash('status', 'Stream deleted.');
    }
    public function render() { $school = Auth::user()->school; return view('livewire.create-class', ['classes' => SchoolClass::with(['streams','classTeacher:id,name'])->where('school_id', $school->id)->orderBy('sort_order')->orderBy('name')->get(), 'teachers'=>User::where('school_id',$school->id)->where('role','teacher')->where('employment_status','active')->orderBy('name')->get(['id','name']), 'educationStages' => \App\Services\SchoolAcademicSetup::stagesFor($school), 'pageTitle' => 'Classes & Streams']); }
}
