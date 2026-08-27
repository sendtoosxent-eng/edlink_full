<?php

namespace App\Livewire;

use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentGuardian;
use App\Models\StudentEnrolment;
use App\Models\Stream;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class StudentRegister extends Component
{
    use WithFileUploads;

    public int $step = 1;

    // Step 1 — Bio data
    public string $name = '';
    public string $date_of_birth = '';
    public string $gender = '';
    public string $admission_date = '';
    public $photo = null;

    // Step 2 — Class data
    public string $school_class_id = '';
    public string $stream_id = '';
    public string $student_category_id = '';

    // Step 3 — Parents data
    public string $guardian_name = '';
    public string $guardian_relationship = 'Parent';
    public string $guardian_phone = '';
    public string $guardian_email = '';
    public string $guardian_address = '';

    // Step 4 — Social data
    public string $nationality = '';
    public string $religion = '';
    public string $blood_group = '';
    public string $home_address = '';
    public string $medical_notes = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
        $this->admission_date = now()->format('Y-m-d');
    }

    public function goToStep2(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'admission_date' => ['required', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
        $this->step = 2;
    }

    public function goToStep3(): void
    {
        $schoolId = Auth::user()->school_id;
        $this->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $schoolId)],
            'stream_id' => ['nullable', Rule::exists('streams', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('school_class_id', $this->school_class_id))],
            'student_category_id' => ['required', Rule::exists('student_categories', 'id')->where('school_id', $schoolId)],
        ]);
        $this->step = 3;
    }

    public function goToStep4(): void
    {
        $this->validate([
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relationship' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:255'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_address' => ['nullable', 'string'],
        ]);
        $this->step = 4;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
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

    public function getStreamsForClassProperty()
    {
        if (! $this->school_class_id) {
            return collect();
        }

        return Stream::where('school_id', Auth::user()->school_id)->where('school_class_id', $this->school_class_id)->orderBy('name')->get();
    }

    public function register(): void
    {
        $this->validate([
            'nationality' => ['nullable', 'string', 'max:255'],
            'religion' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'home_address' => ['nullable', 'string'],
            'medical_notes' => ['nullable', 'string'],
        ]);

        $school = Auth::user()->school;
        $this->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $school->id)],
            'stream_id' => ['nullable', Rule::exists('streams', 'id')->where(fn ($query) => $query->where('school_id', $school->id)->where('school_class_id', $this->school_class_id))],
            'student_category_id' => ['required', Rule::exists('student_categories', 'id')->where('school_id', $school->id)],
        ]);
        $term = $school->currentTerm();
        if (! $school->isLicenceUsable()) {
            $this->addError('school_class_id', 'Student registration is unavailable because the school licence is not active.');
            return;
        }

        if (! $school->hasStudentCapacity()) {
            $this->addError('school_class_id', 'This school has reached its '.number_format((int) $school->license_student_limit).'-student subscription limit. Ask Edlink to upgrade the package.');
            return;
        }

        if (! $term || ! $term->isOpen()) {
            $this->addError('school_class_id', 'Students can only be enrolled during an open current term.');
            return;
        }

        $student = DB::transaction(function () use ($school, $term) {
            $student = Student::create([
                'school_id' => $school->id,
                'school_class_id' => $this->school_class_id,
                'stream_id' => $this->stream_id ?: null,
                'student_category_id' => $this->student_category_id,
                'term_id' => $term?->id,
                'status' => 'active',
                'name' => $this->name,
                'admission_no' => null,
                'date_of_birth' => $this->date_of_birth ?: null,
                'gender' => $this->gender ?: null,
                'admission_date' => $this->admission_date,
                'photo_path' => $this->photo ? $this->photo->store('students/'.$school->id, 'public') : null,
                'nationality' => $this->nationality ?: null,
                'religion' => $this->religion ?: null,
                'blood_group' => $this->blood_group ?: null,
                'home_address' => $this->home_address ?: null,
                'medical_notes' => $this->medical_notes ?: null,
            ]);

            $student->update([
                'admission_no' => app(\App\Services\AdmissionNumberGenerator::class)->generateForStudent($school, $student),
            ]);

            StudentGuardian::create([
                'student_id' => $student->id,
                'name' => $this->guardian_name,
                'relationship' => $this->guardian_relationship ?: null,
                'phone' => $this->guardian_phone ?: null,
                'email' => $this->guardian_email ?: null,
                'address' => $this->guardian_address ?: null,
                'is_primary' => true,
            ]);

            $feeStructure = FeeStructure::where('school_id', $school->id)
                ->where('school_class_id', $student->school_class_id)
                ->where('student_category_id', $student->student_category_id)
                ->where('term_id', $term->id)
                ->first();

            StudentEnrolment::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'term_id' => $term->id,
                'school_class_id' => $student->school_class_id,
                'stream_id' => $student->stream_id,
                'student_category_id' => $student->student_category_id,
                'fee_structure_id' => $feeStructure?->id,
                'base_fee_amount' => $feeStructure?->amount ?? 0,
                'status' => 'active',
                'enrolled_at' => $student->admission_date,
            ]);

            $houseId = DB::table('student_houses as h')
                ->leftJoin('student_house_memberships as m', 'm.student_house_id', '=', 'h.id')
                ->where('h.school_id', $school->id)
                ->groupBy('h.id')
                ->orderByRaw('COUNT(m.id) ASC')
                ->orderBy('h.id')
                ->value('h.id');
            if ($houseId) {
                DB::table('student_house_memberships')->insert([
                    'school_id' => $school->id, 'student_house_id' => $houseId, 'student_id' => $student->id,
                    'allocation_method' => 'automatic', 'assigned_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return $student;
        });

        session()->flash('status', $student->name.' was registered successfully.'.
            ($this->mappedFee ? ' Fee mapped: UGX '.number_format($this->mappedFee).'.' : ''));

        $this->redirect(route('students.index'), navigate: true);
    }


    public function render()
    {
        return view('livewire.student-register', [
            'classes' => SchoolClass::where('school_id', Auth::user()->school_id)->orderBy('name')->get(),
            'categories' => StudentCategory::where('school_id', Auth::user()->school_id)->orderBy('name')->get(),
            'pageTitle' => 'Register Student',
        ]);
    }
}
