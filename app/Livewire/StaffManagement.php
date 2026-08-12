<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Designation;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\StaffNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.app')]
class StaffManagement extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $job_title = 'Teacher';
    public string $role = 'teacher';
    public string $base_salary = '0';
    public string $joined_at = '';
    public string $password = '';
    public string $designationId = '';
    public bool $showInactive = false;
    public ?int $editingId = null;
    public string $editName = '';
    public string $editEmail = '';
    public string $editPhone = '';
    public string $editJobTitle = '';
    public string $editRole = 'teacher';
    public string $editDesignationId = '';
    public string $editBaseSalary = '0';
    public string $editJoinedAt = '';
    public string $editEmploymentStatus = 'active';
    public string $editPassword = '';
    public string $editClassTeacherClassId = '';
    public array $editSubjectAssignments = [];
    public $editPhoto = null;

    public function mount(): void { $this->joined_at = now()->toDateString(); }

    public function add(): void
    {
        $school = Auth::user()->school;
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->where('school_id', $school->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['required', 'string', 'max:100'],
            'role' => ['required', 'in:teacher,bursar,admin'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'joined_at' => ['required', 'date'],
            'password' => ['required', 'string', 'min:8'],
            'designationId' => ['required_unless:role,admin', 'nullable', 'integer'],
        ]);

        try {
            if ($this->designationId && ! Designation::where('school_id', $school->id)->whereKey($this->designationId)->exists()) { $this->addError('designationId', 'Choose a designation created for this school.'); return; }
            $staff = DB::transaction(function () use ($school): User {
                $staff = User::create(['school_id' => $school->id, 'designation_id' => $this->designationId ?: null, 'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone ?: null, 'job_title' => $this->job_title, 'role' => $this->role, 'base_salary' => $this->base_salary, 'employment_status' => 'active', 'joined_at' => $this->joined_at, 'password' => Hash::make($this->password)]);
                $staff->update(['staff_number' => app(StaffNumberGenerator::class)->generate($school, $staff)]);
                $this->syncSchoolAccess($staff, $school);
                return $staff;
            });
            $staff->sendEmailVerificationNotification();
            $this->reset(['name', 'email', 'phone', 'base_salary', 'password', 'designationId']);
            $this->job_title = 'Teacher';
            $this->role = 'teacher';
            session()->flash('status', 'Staff member added.');
        } catch (Throwable $exception) {
            Log::error('Quick staff creation failed.', ['school_id' => $school->id, 'exception' => $exception]);
            $this->addError('add', 'The staff record was not saved. Please try again.');
        }
    }

    public function toggleStatus(int $id): void
    {
        $user = User::where('school_id', Auth::user()->school_id)->findOrFail($id);
        if ($user->id === Auth::id()) { session()->flash('error', 'You cannot deactivate your own account.'); return; }
        $user->update(['employment_status' => $user->employment_status === 'active' ? 'inactive' : 'active']);
        session()->flash('status', $user->name.' marked '.$user->employment_status.'.');
    }

    public function assignDesignation(int $id, string $designationId = ''): void
    {
        $staff = User::where('school_id', Auth::user()->school_id)->findOrFail($id);
        if ($designationId === '' && $staff->role !== 'admin') {
            session()->flash('error', 'Non-admin staff must have an access designation.');
            return;
        }
        if ($designationId !== '' && ! Designation::where('school_id', Auth::user()->school_id)->whereKey($designationId)->exists()) {
            session()->flash('error', 'That designation does not belong to this school.');
            return;
        }
        $staff->update(['designation_id' => $designationId ?: null]);
        $this->syncSchoolAccess($staff->fresh(), Auth::user()->school);
        session()->flash('status', $staff->name.' designation updated.');
    }

    public function edit(int $id): void
    {
        abort_unless(Auth::user()->hasPermission('staff.manage'), 403);
        $staff = User::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $this->editingId = $staff->id;
        $this->editName = $staff->name;
        $this->editEmail = $staff->email;
        $this->editPhone = $staff->phone ?? '';
        $this->editJobTitle = $staff->job_title ?? '';
        $this->editRole = $staff->role;
        $this->editDesignationId = (string) ($staff->designation_id ?? '');
        $this->editBaseSalary = (string) ($staff->base_salary ?? 0);
        $this->editJoinedAt = $staff->joined_at?->toDateString() ?? '';
        $this->editEmploymentStatus = $staff->employment_status ?? 'active';
        $this->editPassword = '';
        $this->editClassTeacherClassId = (string) (SchoolClass::where('school_id', $staff->school_id)->where('class_teacher_user_id', $staff->id)->value('id') ?? '');
        $term = Auth::user()->school->currentTerm();
        $this->editSubjectAssignments = $term ? DB::table('staff_subjects')->where('school_id', $staff->school_id)->where('term_id', $term->id)->where('user_id', $staff->id)->get(['school_class_id', 'subject_id'])->map(fn ($row) => $row->school_class_id.':'.$row->subject_id)->all() : [];
        $this->reset('editPhoto');
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'editName', 'editEmail', 'editPhone', 'editJobTitle', 'editDesignationId', 'editBaseSalary', 'editJoinedAt', 'editPassword', 'editPhoto', 'editClassTeacherClassId', 'editSubjectAssignments']);
        $this->editRole = 'teacher';
        $this->editEmploymentStatus = 'active';
        $this->resetValidation();
    }

    public function updateStaff(): void
    {
        abort_unless(Auth::user()->hasPermission('staff.manage'), 403);
        $staff = User::where('school_id', Auth::user()->school_id)->findOrFail($this->editingId);
        $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', Rule::unique('users', 'email')->where('school_id', Auth::user()->school_id)->ignore($staff->id)],
            'editPhone' => ['nullable', 'string', 'max:30'],
            'editJobTitle' => ['required', 'string', 'max:100'],
            'editRole' => ['required', 'in:teacher,bursar,admin,academic_admin,registrar'],
            'editDesignationId' => ['required_unless:editRole,admin', 'nullable', 'integer'],
            'editBaseSalary' => ['required', 'numeric', 'min:0'],
            'editJoinedAt' => ['nullable', 'date'],
            'editEmploymentStatus' => ['required', 'in:active,inactive'],
            'editPassword' => ['nullable', 'string', 'min:8'],
            'editPhoto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'editClassTeacherClassId' => ['nullable', 'integer'],
            'editSubjectAssignments' => ['array'],
        ]);

        $school = Auth::user()->school;
        if ($this->editClassTeacherClassId && ! SchoolClass::where('school_id', $school->id)->whereKey($this->editClassTeacherClassId)->exists()) {
            $this->addError('editClassTeacherClassId', 'Choose a class belonging to this school.');
            return;
        }
        $pairs = collect($this->editSubjectAssignments)->map(function ($assignment) { [$classId, $subjectId] = array_pad(explode(':', (string) $assignment, 2), 2, null); return ['class_id' => (int) $classId, 'subject_id' => (int) $subjectId]; })->filter(fn ($pair) => $pair['class_id'] && $pair['subject_id'])->values();
        if ($pairs->isNotEmpty() && (SchoolClass::where('school_id', $school->id)->whereIn('id', $pairs->pluck('class_id'))->count() !== $pairs->pluck('class_id')->unique()->count() || Subject::where('school_id', $school->id)->whereIn('id', $pairs->pluck('subject_id'))->count() !== $pairs->pluck('subject_id')->unique()->count())) {
            $this->addError('editSubjectAssignments', 'Choose only classes and subjects belonging to this school.');
            return;
        }

        if ($this->editDesignationId !== '' && ! Designation::where('school_id', Auth::user()->school_id)->whereKey($this->editDesignationId)->exists()) {
            $this->addError('editDesignationId', 'Choose a designation created for this school.');
            return;
        }
        if ($staff->id === Auth::id() && ($this->editRole !== $staff->role || $this->editEmploymentStatus !== 'active')) {
            $this->addError('editRole', 'You cannot change your own account role or deactivate your own account.');
            return;
        }

        $data = [
            'name' => trim($this->editName), 'email' => trim($this->editEmail),
            'phone' => trim($this->editPhone) ?: null, 'job_title' => trim($this->editJobTitle),
            'role' => $this->editRole, 'designation_id' => $this->editDesignationId ?: null,
            'base_salary' => $this->editBaseSalary, 'joined_at' => $this->editJoinedAt ?: null,
            'employment_status' => $this->editEmploymentStatus,
        ];
        if ($this->editPassword !== '') $data['password'] = Hash::make($this->editPassword);
        if ($this->editPhoto) {
            $oldAvatar = $staff->avatar_path;
            $data['avatar_path'] = $this->editPhoto->store('avatars', 'public');
            if ($oldAvatar) Storage::disk('public')->delete($oldAvatar);
        }
        $staff->update($data);
        $this->syncTeachingAssignments($staff->fresh(), $school, $this->editClassTeacherClassId, $this->editSubjectAssignments);
        $this->syncSchoolAccess($staff->fresh(), Auth::user()->school);
        $name = $staff->fresh()->name;
        $this->cancelEdit();
        session()->flash('status', $name.' profile updated.');
    }

    public function render()
    {
        $school = Auth::user()->school;
        return view('livewire.staff-management', ['staff' => User::with('designation')->where('school_id', $school->id)->when(! $this->showInactive, fn ($query) => $query->where('employment_status', 'active'))->orderBy('name')->get(), 'designations' => Designation::where('school_id', $school->id)->orderBy('name')->get(), 'classes' => SchoolClass::where('school_id', $school->id)->orderBy('sort_order')->orderBy('name')->get(), 'subjects' => Subject::where('school_id', $school->id)->orderBy('name')->get(), 'currentTerm' => $school->currentTerm(), 'pageTitle' => 'Staff']);
    }

    private function syncTeachingAssignments(User $staff, $school, string $classTeacherId, array $assignments): void
    {
        SchoolClass::where('school_id', $school->id)->where('class_teacher_user_id', $staff->id)->update(['class_teacher_user_id' => null]);
        if ($classTeacherId) SchoolClass::where('school_id', $school->id)->whereKey($classTeacherId)->update(['class_teacher_user_id' => $staff->id]);
        $term = $school->currentTerm();
        if (! $term) return;
        DB::table('staff_subjects')->where('school_id', $school->id)->where('term_id', $term->id)->where('user_id', $staff->id)->delete();
        foreach ($assignments as $assignment) {
            [$classId, $subjectId] = array_pad(explode(':', (string) $assignment, 2), 2, null);
            if (! $classId || ! $subjectId) continue;
            DB::table('class_subjects')->updateOrInsert(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => (int) $classId, 'subject_id' => (int) $subjectId], ['updated_at' => now(), 'created_at' => now()]);
            DB::table('staff_subjects')->updateOrInsert(['school_id' => $school->id, 'term_id' => $term->id, 'user_id' => $staff->id, 'school_class_id' => (int) $classId, 'subject_id' => (int) $subjectId], ['updated_at' => now(), 'created_at' => now()]);
        }
    }

    private function syncSchoolAccess(User $staff, $school): void
    {
        if (! Schema::hasTable('school_user_access')) return;

        DB::table('school_user_access')->updateOrInsert(
            ['school_id' => $school->id, 'user_id' => $staff->id],
            ['role' => $staff->role, 'designation_id' => $staff->designation_id, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
