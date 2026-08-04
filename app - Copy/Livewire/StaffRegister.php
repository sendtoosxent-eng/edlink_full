<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Designation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.app')]
class StaffRegister extends Component
{
    use WithFileUploads;

    public int $step = 1;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public $photo = null;
    public string $password = '';
    public string $password_confirmation = '';
    public string $job_title = 'Teacher';
    public string $role = 'teacher';
    public string $joined_at = '';
    public string $base_salary = '';
    public string $employment_status = 'active';
    public string $designation_id = '';

    public function mount(): void
    {
        $this->joined_at = now()->toDateString();
    }

    private function schoolEmailRule(): Unique
    {
        return Rule::unique('users', 'email')->where('school_id', Auth::user()->school_id);
    }

    public function next(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', $this->schoolEmailRule()],
                'phone' => 'nullable|string|max:30',
                'photo' => 'nullable|image|max:2048',
                'password' => 'required|string|min:8|confirmed',
            ]);
        }

        if ($this->step === 2) {
            $this->validate([
                'job_title' => 'required|string|max:100',
                'role' => 'required|in:teacher,bursar,admin',
                'designation_id' => 'required_unless:role,admin|nullable|integer',
                'joined_at' => 'required|date',
            ]);
        }

        if ($this->step < 3) {
            $this->step++;
        }
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function register(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', $this->schoolEmailRule()],
            'phone' => 'nullable|string|max:30',
            'photo' => 'nullable|image|max:2048',
            'password' => 'required|string|min:8|confirmed',
            'job_title' => 'required|string|max:100',
            'role' => 'required|in:teacher,bursar,admin',
            'designation_id' => 'required_unless:role,admin|nullable|integer',
            'joined_at' => 'required|date',
            'base_salary' => 'required|numeric|min:0',
            'employment_status' => 'required|in:active,inactive',
        ]);

        try {
            $school = Auth::user()->school;
            if ($this->designation_id && ! Designation::where('school_id', $school->id)->whereKey($this->designation_id)->exists()) {
                $this->addError('designation_id', 'Choose a designation created for this school.');
                return;
            }
            $number = 'STF-'.str_pad((string) ($school->users()->count() + 1), 4, '0', STR_PAD_LEFT);

            User::create([
                'school_id' => $school->id,
                'designation_id' => $this->designation_id ?: null,
                'staff_number' => $number,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?: null,
                'avatar_path' => $this->photo ? $this->photo->store('avatars', 'public') : null,
                'password' => Hash::make($this->password),
                'job_title' => $this->job_title,
                'role' => $this->role,
                'joined_at' => $this->joined_at,
                'base_salary' => $this->base_salary,
                'employment_status' => $this->employment_status,
            ]);

            session()->flash('status', $this->name.' was registered successfully.');
            $this->redirect(route('staff.index'), navigate: true);
        } catch (Throwable $exception) {
            Log::error('Staff registration failed.', ['school_id' => Auth::user()->school_id, 'exception' => $exception]);
            $this->addError('register', 'The staff record was not saved. Please review the details and try again.');
        }
    }

    public function render()
    {
        return view('livewire.staff-register', ['designations' => Designation::where('school_id', Auth::user()->school_id)->orderBy('name')->get(), 'pageTitle' => 'Register Staff']);
    }
}
