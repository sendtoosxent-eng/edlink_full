<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PortalAccess extends Component
{
    public string $studentId = '';
    public string $userId = '';
    public string $role = 'parent';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $relationship = 'Parent';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
    }

    public function linkExisting(): void
    {
        $this->validate([
            'studentId' => ['required', 'exists:students,id'],
            'userId' => ['required', 'exists:users,id'],
            'relationship' => ['required', 'string', 'max:50'],
        ]);
        $schoolId = Auth::user()->school_id;
        $student = Student::where('school_id', $schoolId)->findOrFail($this->studentId);
        $user = User::where('school_id', $schoolId)->whereIn('role', ['parent', 'student'])->findOrFail($this->userId);

        DB::table('portal_user_students')->updateOrInsert(
            ['user_id' => $user->id, 'student_id' => $student->id],
            ['school_id' => $schoolId, 'relationship' => $this->relationship, 'updated_at' => now(), 'created_at' => now()]
        );
        $this->reset(['userId', 'relationship']);
        $this->relationship = 'Parent';
        session()->flash('status', 'Portal account linked to learner.');
    }

    public function createAndLink(): void
    {
        $this->validate([
            'studentId' => ['required', 'exists:students,id'],
            'role' => ['required', Rule::in(['parent', 'student'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users','email')->where('school_id', Auth::user()->school_id)],
            'password' => ['required', 'string', 'min:8'],
            'relationship' => ['required', 'string', 'max:50'],
        ]);
        $schoolId = Auth::user()->school_id;
        $student = Student::where('school_id', $schoolId)->findOrFail($this->studentId);
        $user = User::create([
            'school_id' => $schoolId, 'name' => $this->name, 'email' => $this->email,
            'password' => $this->password, 'role' => $this->role, 'employment_status' => 'active',
        ]);
        DB::table('portal_user_students')->insert([
            'school_id' => $schoolId, 'user_id' => $user->id, 'student_id' => $student->id,
            'relationship' => $this->relationship, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->reset(['userId', 'name', 'email', 'password', 'relationship']);
        $this->role = 'parent';
        $this->relationship = 'Parent';
        session()->flash('status', 'Portal account created and linked. Give the temporary password to the account holder securely.');
    }

    public function unlink(int $id): void
    {
        DB::table('portal_user_students')->where('id', $id)->where('school_id', Auth::user()->school_id)->delete();
        session()->flash('status', 'Portal link removed. The learner and login account were kept.');
    }

    public function render()
    {
        $schoolId = Auth::user()->school_id;
        return view('livewire.portal-access', [
            'students' => Student::where('school_id', $schoolId)->orderBy('name')->get(),
            'accounts' => User::where('school_id', $schoolId)->whereIn('role', ['parent', 'student'])->orderBy('name')->get(),
            'links' => DB::table('portal_user_students as link')->join('students', 'students.id', '=', 'link.student_id')->join('users', 'users.id', '=', 'link.user_id')->where('link.school_id', $schoolId)->orderByDesc('link.id')->get(['link.id', 'link.relationship', 'students.name as student_name', 'students.admission_no', 'users.name as user_name', 'users.email', 'users.role']),
            'pageTitle' => 'Portal Access',
        ]);
    }
}
