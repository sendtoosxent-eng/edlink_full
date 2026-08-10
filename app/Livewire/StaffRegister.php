<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Designation;
use App\Models\AuditLog;
use App\Services\StaffNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public bool $admin_confirmation = false;
    public string $emergency_contact_name = '';
    public string $emergency_contact_phone = '';
    public string $national_id = '';
    public string $contract_type = 'permanent';
    public string $probation_ends_at = '';
    public string $bank_name = '';
    public string $bank_account_name = '';
    public string $bank_account_number = '';
    public string $document_type = '';
    public $document_file = null;

    public function mount(): void
    {
        $this->joined_at = now()->toDateString();
    }

    private function schoolEmailRule(): Unique
    {
        return Rule::unique('users', 'email')->where('school_id', Auth::user()->school_id);
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'Enter the staff member’s full name.',
            'email.required' => 'Enter an email address.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'That email is already registered in this school.',
            'password.required' => 'Enter a temporary password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'job_title.required' => 'Enter the staff member’s job title.',
            'role.required' => 'Select an account type.',
            'designation_id.required_unless' => 'Select a designation before continuing.',
            'designation_id.integer' => 'Select a valid designation.',
            'joined_at.required' => 'Select the joining date.',
            'base_salary.required' => 'Enter the monthly salary, or enter 0.',
            'base_salary.numeric' => 'Salary must be a valid number.',
            'admin_confirmation.accepted_if' => 'Confirm administrator access before continuing.',
            'document_file.mimes' => 'The document must be a PDF, JPG, JPEG, or PNG file.',
        ];
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
                'admin_confirmation' => 'accepted_if:role,admin',
            ], $this->validationMessages());
        }

        if ($this->step === 2) {
            $this->validate([
                'job_title' => 'required|string|max:100',
                'role' => 'required|in:teacher,bursar,admin',
                'designation_id' => 'required_unless:role,admin|nullable|integer',
                'joined_at' => 'required|date',
                'admin_confirmation' => 'accepted_if:role,admin',
            ], $this->validationMessages());
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
            'document_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'password' => 'required|string|min:8|confirmed',
            'job_title' => 'required|string|max:100',
            'role' => 'required|in:teacher,bursar,admin',
            'designation_id' => 'required_unless:role,admin|nullable|integer',
            'joined_at' => 'required|date',
            'base_salary' => 'required|numeric|min:0',
            'employment_status' => 'required|in:active,inactive',
            'admin_confirmation' => 'accepted_if:role,admin',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'national_id' => 'nullable|string|max:100',
            'contract_type' => 'required|in:permanent,contract,part_time,volunteer',
            'probation_ends_at' => 'nullable|date|after_or_equal:joined_at',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'document_type' => 'nullable|string|max:100',
        ], $this->validationMessages());

        try {
            $school = Auth::user()->school;
            if ($this->designation_id && ! Designation::where('school_id', $school->id)->whereKey($this->designation_id)->exists()) {
                $this->addError('designation_id', 'Choose a designation created for this school.');
                return;
            }
            DB::transaction(function () use ($school): void {
                $staff = User::create([
                    'school_id' => $school->id,
                    'designation_id' => $this->designation_id ?: null,
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone ?: null,
                    'avatar_path' => $this->photo ? $this->photo->store('avatars', 'public') : null,
                    // Keep identity documents on the non-public local disk.
                    'staff_document_path' => $this->document_file ? $this->document_file->store('staff-documents', 'local') : null,
                    'staff_document_type' => $this->document_type ?: null,
                    'password' => Hash::make($this->password),
                    'job_title' => $this->job_title,
                    'role' => $this->role,
                    'joined_at' => $this->joined_at,
                    'base_salary' => $this->base_salary,
                    'employment_status' => $this->employment_status,
                    'emergency_contact_name' => $this->emergency_contact_name ?: null,
                    'emergency_contact_phone' => $this->emergency_contact_phone ?: null,
                    'national_id' => $this->national_id ?: null,
                    'contract_type' => $this->contract_type,
                    'probation_ends_at' => $this->probation_ends_at ?: null,
                    'bank_name' => $this->bank_name ?: null,
                    'bank_account_name' => $this->bank_account_name ?: null,
                    'bank_account_number' => $this->bank_account_number ?: null,
                ]);

                $staff->update(['staff_number' => app(StaffNumberGenerator::class)->generate($school, $staff)]);
                $staff->sendEmailVerificationNotification();
                AuditLog::record($school->id, 'staff.registered', $staff, [
                    'staff_number' => $staff->staff_number,
                    'role' => $staff->role,
                    'employment_status' => $staff->employment_status,
                ]);
            });

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
