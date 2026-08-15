<?php

namespace App\Livewire;

use App\Models\Designation;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\DefaultDesignationService;
use App\Services\StaffRegistrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class StaffRegister extends Component
{
    public int $step = 1;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $job_title = 'Teacher';

    public string $role = 'teacher';

    public string $designation_id = '';

    public string $joined_at = '';

    public string $base_salary = '0';

    public string $employment_status = 'active';

    public string $contract_type = 'permanent';

    public string $probation_ends_at = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public string $national_id = '';

    public string $bank_name = '';

    public string $bank_account_name = '';

    public string $bank_account_number = '';

    public bool $admin_confirmation = false;

    public bool $has_teaching_duties = true;

    public bool $is_class_teacher = false;

    public string $class_teacher_class_id = '';

    /** @var array<int, array{class_id:string, subject_ids:array<int, string>}> */
    public array $teaching_assignments = [];

    public function mount(DefaultDesignationService $designations): void
    {
        $this->joined_at = now()->toDateString();
        $this->teaching_assignments = [$this->emptyTeachingAssignment()];
        $designations->ensureFor(Auth::user()->school);
    }

    public function updatedRole(string $role): void
    {
        if ($role === 'teacher') {
            $this->has_teaching_duties = true;
            $this->job_title = $this->job_title === '' ? 'Teacher' : $this->job_title;
        } else {
            $this->has_teaching_duties = false;
            $this->clearTeachingAssignments();
        }

        if ($role === 'admin') {
            $this->designation_id = '';

            return;
        }

        $defaultDesignationName = match ($role) {
            'teacher' => 'Subject Teacher',
            'bursar' => 'Bursar',
            'academic_admin' => 'DOS',
            default => null,
        };

        if ($defaultDesignationName) {
            $designation = Designation::where('school_id', Auth::user()->school_id)
                ->where('name', $defaultDesignationName)
                ->first();
            $this->designation_id = (string) ($designation?->id ?? '');

            if ($this->job_title === '' || in_array($this->job_title, ['Teacher', 'Bursar', 'DOS'], true)) {
                $this->job_title = $defaultDesignationName;
            }
        }
    }

    public function updatedDesignationId(string $designationId): void
    {
        $designation = Designation::where('school_id', Auth::user()->school_id)->find($designationId);
        if (! $designation) {
            return;
        }

        if ($this->job_title === '' || in_array($this->job_title, ['Teacher', 'Bursar', 'DOS'], true)) {
            $this->job_title = $designation->name;
        }

        $designationName = strtolower($designation->name);

        if (str_contains($designationName, 'teacher')) {
            $this->role = 'teacher';
            $this->has_teaching_duties = true;
            $this->is_class_teacher = str_contains($designationName, 'class teacher');
        } elseif ($designationName === 'bursar') {
            $this->role = 'bursar';
            $this->has_teaching_duties = false;
            $this->clearTeachingAssignments();
        } elseif ($designationName === 'dos') {
            $this->role = 'academic_admin';
            $this->has_teaching_duties = false;
            $this->clearTeachingAssignments();
        }
    }

    public function updatedHasTeachingDuties(bool $hasTeachingDuties): void
    {
        if (! $hasTeachingDuties && $this->role !== 'teacher') {
            $this->clearTeachingAssignments();
        }
    }

    public function updatedIsClassTeacher(bool $isClassTeacher): void
    {
        if (! $isClassTeacher) {
            $this->class_teacher_class_id = '';
        }
    }

    public function addTeachingAssignment(): void
    {
        $this->teaching_assignments[] = $this->emptyTeachingAssignment();
    }

    public function removeTeachingAssignment(int $index): void
    {
        unset($this->teaching_assignments[$index]);
        $this->teaching_assignments = array_values($this->teaching_assignments);

        if ($this->teaching_assignments === []) {
            $this->teaching_assignments[] = $this->emptyTeachingAssignment();
        }
    }

    public function next(): void
    {
        $this->normalizeIdentity();

        if ($this->step === 1) {
            $this->validate($this->identityRules(), $this->validationMessages());
        } elseif ($this->step === 2) {
            $this->validate($this->employmentRules(), $this->validationMessages());
            $this->validateDesignation();
        }

        $this->step = min(3, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function register(StaffRegistrationService $registration): void
    {
        abort_unless(Auth::user()->hasPermission('staff.manage'), 403);
        $this->normalizeIdentity();
        $this->has_teaching_duties = $this->role === 'teacher' || $this->has_teaching_duties;

        $this->validate([
            ...$this->identityRules(),
            ...$this->employmentRules(),
            'has_teaching_duties' => ['boolean'],
            'is_class_teacher' => ['boolean'],
            'class_teacher_class_id' => ['nullable', 'integer'],
            'teaching_assignments' => ['array'],
            'teaching_assignments.*.class_id' => ['nullable', 'integer'],
            'teaching_assignments.*.subject_ids' => ['array'],
            'teaching_assignments.*.subject_ids.*' => ['integer'],
        ], $this->validationMessages());

        $designation = $this->validateDesignation();
        if ($this->role === 'admin' && ! in_array(Auth::user()->role, ['admin', 'superadmin'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Only a school administrator may create another administrator.',
            ]);
        }
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        $subjectAssignments = $this->validatedSubjectAssignments();
        $classTeacherClassId = $this->validatedClassTeacherClassId();

        if ($this->has_teaching_duties && ! $term) {
            $this->addError('teaching_assignments', 'Open a current term before assigning teaching responsibilities.');

            return;
        }

        if ($this->has_teaching_duties && $subjectAssignments === []) {
            $this->addError('teaching_assignments', 'Assign at least one subject and class to teaching staff.');

            return;
        }

        try {
            $staff = $registration->register(
                $school,
                [
                    'designation_id' => $designation?->id,
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone ?: null,
                    'password' => $this->password,
                    'job_title' => $this->job_title,
                    'role' => $this->role,
                    'joined_at' => $this->joined_at,
                    'base_salary' => $this->base_salary,
                    'employment_status' => $this->employment_status,
                    'contract_type' => $this->contract_type,
                    'probation_ends_at' => $this->probation_ends_at ?: null,
                    'emergency_contact_name' => $this->emergency_contact_name ?: null,
                    'emergency_contact_phone' => $this->emergency_contact_phone ?: null,
                    'national_id' => $this->national_id ?: null,
                    'bank_name' => $this->bank_name ?: null,
                    'bank_account_name' => $this->bank_account_name ?: null,
                    'bank_account_number' => $this->bank_account_number ?: null,
                ],
                $term,
                $classTeacherClassId,
                $subjectAssignments,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Staff registration failed.', [
                'school_id' => $school->id,
                'email' => $this->email,
                'exception' => $exception,
            ]);
            $this->addError('register', 'The staff record was not saved. Please review the details and try again.');

            return;
        }

        try {
            $staff->sendEmailVerificationNotification();
            $message = $staff->name.' was registered. A verification link has been sent to '.$staff->email.'.';
        } catch (Throwable $exception) {
            Log::warning('Staff registered but verification email delivery failed.', [
                'school_id' => $school->id,
                'staff_id' => $staff->id,
                'exception' => $exception,
            ]);
            $message = $staff->name.' was registered, but the verification email could not be delivered. Use Resend verification from the staff directory.';
        }

        session()->flash('status', $message);
        $this->redirect(route('staff.index'), navigate: true);
    }

    private function identityRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:filter', 'max:255', $this->schoolEmailRule()],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    private function employmentRules(): array
    {
        return [
            'job_title' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(['teacher', 'bursar', 'registrar', 'academic_admin', 'admin'])],
            'designation_id' => ['required_unless:role,admin', 'nullable', 'integer'],
            'joined_at' => ['required', 'date'],
            'base_salary' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'employment_status' => ['required', Rule::in(['active', 'inactive'])],
            'contract_type' => ['required', Rule::in(['permanent', 'contract', 'part_time', 'volunteer'])],
            'probation_ends_at' => ['nullable', 'date', 'after_or_equal:joined_at'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'admin_confirmation' => ['accepted_if:role,admin'],
        ];
    }

    private function validateDesignation(): ?Designation
    {
        if ($this->role === 'admin') {
            return null;
        }

        $designation = Designation::where('school_id', Auth::user()->school_id)->find($this->designation_id);
        if (! $designation) {
            throw ValidationException::withMessages([
                'designation_id' => 'Choose a designation created for this school.',
            ]);
        }

        return $designation;
    }

    /** @return array<int, array{class_id:int, subject_id:int}> */
    private function validatedSubjectAssignments(): array
    {
        if (! $this->has_teaching_duties) {
            return [];
        }

        $schoolId = Auth::user()->school_id;
        $pairs = collect($this->teaching_assignments)
            ->flatMap(function (array $assignment): array {
                $classId = (int) ($assignment['class_id'] ?? 0);

                return collect($assignment['subject_ids'] ?? [])->map(fn ($subjectId): array => [
                    'class_id' => $classId,
                    'subject_id' => (int) $subjectId,
                ])->all();
            })
            ->filter(fn (array $assignment): bool => $assignment['class_id'] > 0 && $assignment['subject_id'] > 0)
            ->unique(fn (array $assignment): string => $assignment['class_id'].':'.$assignment['subject_id'])
            ->values();

        if ($pairs->isEmpty()) {
            return [];
        }

        $classIds = $pairs->pluck('class_id')->unique();
        $subjectIds = $pairs->pluck('subject_id')->unique();
        $validClassCount = SchoolClass::where('school_id', $schoolId)->whereIn('id', $classIds)->count();
        $validSubjectCount = Subject::where('school_id', $schoolId)->whereIn('id', $subjectIds)->count();

        if ($validClassCount !== $classIds->count() || $validSubjectCount !== $subjectIds->count()) {
            throw ValidationException::withMessages([
                'teaching_assignments' => 'Choose only classes and subjects belonging to this school.',
            ]);
        }

        return $pairs->all();
    }

    private function validatedClassTeacherClassId(): ?int
    {
        if (! $this->has_teaching_duties || ! $this->is_class_teacher) {
            return null;
        }

        if ($this->class_teacher_class_id === '') {
            throw ValidationException::withMessages([
                'class_teacher_class_id' => 'Choose the class assigned to this class teacher.',
            ]);
        }

        $class = SchoolClass::where('school_id', Auth::user()->school_id)->find($this->class_teacher_class_id);
        if (! $class) {
            throw ValidationException::withMessages([
                'class_teacher_class_id' => 'Choose a class belonging to this school.',
            ]);
        }

        if ($class->class_teacher_user_id) {
            throw ValidationException::withMessages([
                'class_teacher_class_id' => 'That class already has a class teacher. Reassign it from Classes & Streams first.',
            ]);
        }

        return $class->id;
    }

    private function schoolEmailRule(): Unique
    {
        return Rule::unique('users', 'email')->where('school_id', Auth::user()->school_id);
    }

    private function normalizeIdentity(): void
    {
        $this->name = trim(preg_replace('/\s+/', ' ', $this->name) ?? $this->name);
        $this->email = strtolower(trim($this->email));
        $this->phone = trim($this->phone);
    }

    private function clearTeachingAssignments(): void
    {
        $this->is_class_teacher = false;
        $this->class_teacher_class_id = '';
        $this->teaching_assignments = [$this->emptyTeachingAssignment()];
    }

    /** @return array{class_id:string, subject_ids:array<int, string>} */
    private function emptyTeachingAssignment(): array
    {
        return ['class_id' => '', 'subject_ids' => []];
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'Enter the staff member\'s full name.',
            'email.required' => 'Enter an email address.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'That email is already registered in this school.',
            'password.required' => 'Enter a temporary password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'job_title.required' => 'Enter the staff member\'s job title.',
            'designation_id.required_unless' => 'Select a designation before continuing.',
            'joined_at.required' => 'Select the joining date.',
            'base_salary.required' => 'Enter the monthly salary, or enter 0.',
            'base_salary.numeric' => 'Salary must be a valid number.',
            'admin_confirmation.accepted_if' => 'Confirm administrator access before continuing.',
        ];
    }

    public function render()
    {
        $school = Auth::user()->school;

        return view('livewire.staff-register', [
            'designations' => Designation::where('school_id', $school->id)->orderBy('name')->get(),
            'classes' => SchoolClass::with('classTeacher:id,name')
                ->where('school_id', $school->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'subjects' => Subject::where('school_id', $school->id)->orderBy('name')->get(),
            'currentTerm' => $school->currentTerm(),
            'pageTitle' => 'Register Staff',
        ]);
    }
}
