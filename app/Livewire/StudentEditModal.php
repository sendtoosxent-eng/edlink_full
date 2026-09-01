<?php

namespace App\Livewire;

use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentGuardian;
use App\Services\PublicImageStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class StudentEditModal extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public ?int $studentId = null;

    // Bio
    public string $name = '';

    public string $admission_no = '';

    public string $date_of_birth = '';

    public string $gender = '';

    public $photo = null;

    // Class
    public string $school_class_id = '';

    public string $stream_id = '';

    public string $student_category_id = '';

    // Guardian (primary)
    public ?int $guardianId = null;

    public string $guardian_name = '';

    public string $guardian_relationship = '';

    public string $guardian_phone = '';

    public string $guardian_email = '';

    public string $guardian_address = '';

    // Social
    public string $nationality = '';

    public string $religion = '';

    public string $blood_group = '';

    public string $home_address = '';

    public string $medical_notes = '';

    #[On('edit-student')]
    public function open(int $studentId): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $student = Student::with('guardians')->where('school_id', Auth::user()->school_id)->findOrFail($studentId);

        $this->resetValidation();
        $this->studentId = $student->id;
        $this->name = $student->name;
        $this->admission_no = $student->admission_no ?? '';
        $this->date_of_birth = $student->date_of_birth?->format('Y-m-d') ?? '';
        $this->gender = $student->gender ?? '';
        $this->photo = null;

        $this->school_class_id = (string) $student->school_class_id;
        $this->stream_id = (string) ($student->stream_id ?? '');
        $this->student_category_id = (string) ($student->student_category_id ?? '');

        $guardian = $student->guardians->firstWhere('is_primary', true) ?? $student->guardians->first();
        $this->guardianId = $guardian?->id;
        $this->guardian_name = $guardian->name ?? '';
        $this->guardian_relationship = $guardian->relationship ?? '';
        $this->guardian_phone = $guardian->phone ?? '';
        $this->guardian_email = $guardian->email ?? '';
        $this->guardian_address = $guardian->address ?? '';

        $this->nationality = $student->nationality ?? '';
        $this->religion = $student->religion ?? '';
        $this->blood_group = $student->blood_group ?? '';
        $this->home_address = $student->home_address ?? '';
        $this->medical_notes = $student->medical_notes ?? '';

        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function getStreamsForClassProperty()
    {
        if (! $this->school_class_id) {
            return collect();
        }

        return Stream::where('school_id', Auth::user()->school_id)->where('school_class_id', $this->school_class_id)->orderBy('name')->get();
    }

    public function getMappedFeeProperty(): ?float
    {
        if (! $this->school_class_id || ! $this->student_category_id) {
            return null;
        }
        $class = SchoolClass::where('school_id', Auth::user()->school_id)->find($this->school_class_id);
        $category = StudentCategory::where('school_id', Auth::user()->school_id)->find($this->student_category_id);
        $term = Auth::user()->school->currentTerm();

        if (! $class || ! $category || ! $term) {
            return null;
        }

        return FeeStructure::amountFor($class, $category, $term);
    }

    public function save(PublicImageStorage $images): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $student = Student::where('school_id', Auth::user()->school_id)->findOrFail($this->studentId);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'admission_no' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', Auth::user()->school_id)],
            'stream_id' => ['nullable', Rule::exists('streams', 'id')->where(fn ($query) => $query->where('school_id', Auth::user()->school_id)->where('school_class_id', $this->school_class_id))],
            'student_category_id' => ['required', Rule::exists('student_categories', 'id')->where('school_id', Auth::user()->school_id)],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
        ]);

        $student->name = $this->name;
        $student->admission_no = $this->admission_no ?: null;
        $student->date_of_birth = $this->date_of_birth ?: null;
        $student->gender = $this->gender ?: null;
        $student->school_class_id = $this->school_class_id;
        $student->stream_id = $this->stream_id ?: null;
        $student->student_category_id = $this->student_category_id;
        $student->nationality = $this->nationality ?: null;
        $student->religion = $this->religion ?: null;
        $student->blood_group = $this->blood_group ?: null;
        $student->home_address = $this->home_address ?: null;
        $student->medical_notes = $this->medical_notes ?: null;

        if ($this->photo) {
            $oldPhoto = $student->photo_path;
            $student->photo_path = $images->store($this->photo, 'students/'.$student->school_id);
            $images->deleteReplacement($oldPhoto, $student->photo_path);
        }

        $student->save();

        if ($this->guardianId) {
            $student->guardians()->whereKey($this->guardianId)->firstOrFail()->update([
                'name' => $this->guardian_name,
                'relationship' => $this->guardian_relationship ?: null,
                'phone' => $this->guardian_phone ?: null,
                'email' => $this->guardian_email ?: null,
                'address' => $this->guardian_address ?: null,
            ]);
        } else {
            StudentGuardian::create([
                'student_id' => $student->id,
                'name' => $this->guardian_name,
                'relationship' => $this->guardian_relationship ?: null,
                'phone' => $this->guardian_phone ?: null,
                'email' => $this->guardian_email ?: null,
                'address' => $this->guardian_address ?: null,
                'is_primary' => true,
            ]);
        }

        $this->photo = null;
        $this->isOpen = false;
        session()->flash('status', $student->name.'\'s record was updated.');
        $this->dispatch('student-updated');
    }

    public function render()
    {
        return view('livewire.student-edit-modal', [
            'classes' => SchoolClass::where('school_id', Auth::user()->school_id)->orderBy('name')->get(),
            'categories' => StudentCategory::where('school_id', Auth::user()->school_id)->orderBy('name')->get(),
        ]);
    }
}
