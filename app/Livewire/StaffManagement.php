<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Designation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
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
            'designationId' => ['nullable', 'integer'],
        ]);

        try {
            $number = 'STF-'.str_pad((string) ($school->users()->count() + 1), 4, '0', STR_PAD_LEFT);
            if ($this->designationId && ! Designation::where('school_id', $school->id)->whereKey($this->designationId)->exists()) { $this->addError('designationId', 'Choose a designation created for this school.'); return; }
            User::create(['school_id' => $school->id, 'designation_id' => $this->designationId ?: null, 'staff_number' => $number, 'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone ?: null, 'job_title' => $this->job_title, 'role' => $this->role, 'base_salary' => $this->base_salary, 'employment_status' => 'active', 'joined_at' => $this->joined_at, 'password' => Hash::make($this->password)]);
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
        if ($designationId !== '' && ! Designation::where('school_id', Auth::user()->school_id)->whereKey($designationId)->exists()) {
            session()->flash('error', 'That designation does not belong to this school.');
            return;
        }
        $staff->update(['designation_id' => $designationId ?: null]);
        session()->flash('status', $staff->name.' designation updated.');
    }

    public function render()
    {
        return view('livewire.staff-management', ['staff' => User::with('designation')->where('school_id', Auth::user()->school_id)->when(! $this->showInactive, fn ($query) => $query->where('employment_status', 'active'), fn ($query) => $query->where('employment_status', 'inactive'))->orderBy('name')->get(), 'designations' => Designation::where('school_id', Auth::user()->school_id)->orderBy('name')->get(), 'pageTitle' => 'Staff']);
    }
}
