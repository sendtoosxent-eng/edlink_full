<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Designation;
use App\Services\StaffNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            DB::transaction(function () use ($school): void {
                $staff = User::create(['school_id' => $school->id, 'designation_id' => $this->designationId ?: null, 'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone ?: null, 'job_title' => $this->job_title, 'role' => $this->role, 'base_salary' => $this->base_salary, 'employment_status' => 'active', 'joined_at' => $this->joined_at, 'password' => Hash::make($this->password)]);
                $staff->update(['staff_number' => app(StaffNumberGenerator::class)->generate($school, $staff)]);
            });
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
        $this->reset('editPhoto');
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'editName', 'editEmail', 'editPhone', 'editJobTitle', 'editDesignationId', 'editBaseSalary', 'editJoinedAt', 'editPassword', 'editPhoto']);
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
        ]);

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
        $name = $staff->fresh()->name;
        $this->cancelEdit();
        session()->flash('status', $name.' profile updated.');
    }

    public function render()
    {
        return view('livewire.staff-management', ['staff' => User::with('designation')->where('school_id', Auth::user()->school_id)->when(! $this->showInactive, fn ($query) => $query->where('employment_status', 'active'))->orderBy('name')->get(), 'designations' => Designation::where('school_id', Auth::user()->school_id)->orderBy('name')->get(), 'pageTitle' => 'Staff']);
    }
}
