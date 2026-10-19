<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PublicImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonProfileController extends Controller
{
    public function student(Student $student): View
    {
        $this->authorizeStudent($student);

        return view('profiles.person', [
            'type' => 'student',
            'person' => $student->load(['schoolClass', 'stream', 'category', 'guardians']),
            'backRoute' => route('students.index'),
            'updateRoute' => route('students.profile.update', $student),
            'canEdit' => request()->user()->hasPermission('students.manage'),
        ]);
    }

    public function updateStudent(Request $request, Student $student, PublicImageStorage $images): RedirectResponse
    {
        $this->authorizeStudent($student);
        abort_unless($request->user()->hasPermission('students.manage'), 403);
        $request->merge([
            'lin' => filled($request->input('lin')) ? strtoupper(trim((string) $request->input('lin'))) : null,
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'admission_no' => ['nullable', 'string', 'max:100', Rule::unique('students', 'admission_no')->where('school_id', $request->user()->school_id)->ignore($student->id)],
            'lin' => ['nullable', 'string', 'max:100', Rule::unique('students', 'lin')->where('school_id', $request->user()->school_id)->ignore($student->id)],
            'date_of_birth' => ['nullable', 'date'], 'gender' => ['nullable', 'in:male,female'],
            'nationality' => ['nullable', 'string', 'max:100'], 'religion' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', 'string', 'max:10'], 'home_address' => ['nullable', 'string', 'max:500'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $photo = $data['photo'] ?? null;
        unset($data['photo']);
        if ($photo) {
            $oldPath = $student->photo_path;
            $data['photo_path'] = $images->store($photo, 'students/'.$student->school_id);
            $images->deleteReplacement($oldPath, $data['photo_path']);
        }
        $student->update($data);

        return back()->with('status', 'Student profile and photo saved. The same photo will be used on ID cards.');
    }

    public function staff(User $user): View
    {
        $this->authorizeUser($user, ['admin', 'superadmin', 'teacher', 'bursar', 'registrar', 'academic_admin'], 'staff.directory');

        $viewerCanSeePayroll = request()->user()->hasPermission('staff.payroll');
        $payrollRuns = $viewerCanSeePayroll
            ? PayrollRun::posted()->where('school_id', $user->school_id)->where('user_id', $user->id)->latest('paid_at')->limit(24)->get()
            : collect();
        $payrollPeriod = now()->format('Y-m');
        $salaryPaid = (float) $payrollRuns->where('period', $payrollPeriod)->where('payment_type', 'salary')->sum('amount');
        $salaryOutstanding = max(0, (float) $user->base_salary - $salaryPaid);

        return $this->userView($user->load('designation'), 'staff', route('staff.index'), route('staff.profile.update', $user), request()->user()->hasPermission('staff.manage'), [
            'payrollRuns' => $payrollRuns,
            'payrollPeriod' => $payrollPeriod,
            'salaryPaid' => $salaryPaid,
            'salaryOutstanding' => $salaryOutstanding,
            'viewerCanSeePayroll' => $viewerCanSeePayroll,
        ]);
    }

    public function parent(User $user): View
    {
        $this->authorizeUser($user, ['parent'], 'parents.manage');

        return $this->userView($user->load('portalStudents.schoolClass'), 'parent', route('parents.index'), route('parents.profile.update', $user), request()->user()->hasPermission('parents.manage'), [
            'availableStudents' => Student::where('school_id', $user->school_id)->orderBy('name')->get(),
        ]);
    }

    public function updateStaff(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user, ['admin', 'superadmin', 'teacher', 'bursar', 'registrar', 'academic_admin'], 'staff.manage');

        return $this->updateUser($request, $user, true, app(PublicImageStorage::class));
    }

    public function updateParent(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user, ['parent'], 'parents.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->where('school_id', $request->user()->school_id)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'studentIds' => ['nullable', 'array'],
            'studentIds.*' => [Rule::exists('students', 'id')->where('school_id', $request->user()->school_id)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $photo = $data['photo'] ?? null;
        unset($data['photo'], $data['studentIds']);
        if (blank($data['password'] ?? null)) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        if ($photo) {
            $oldPath = $user->avatar_path;
            $data['avatar_path'] = app(PublicImageStorage::class)->store($photo, 'avatars/'.$user->school_id);
            app(PublicImageStorage::class)->deleteReplacement($oldPath, $data['avatar_path']);
        }
        DB::transaction(function () use ($user, $data, $request): void {
            $user->update($data);
            $studentIds = collect($request->input('studentIds', []))->map(fn ($id) => (int) $id)->unique();
            DB::table('portal_user_students')->where('school_id', $user->school_id)->where('user_id', $user->id)->delete();
            foreach ($studentIds as $studentId) DB::table('portal_user_students')->insert(['school_id' => $user->school_id, 'user_id' => $user->id, 'student_id' => $studentId, 'relationship' => 'Parent', 'created_at' => now(), 'updated_at' => now()]);
        });
        return back()->with('status', 'Parent profile, password, and learner links saved.');
    }

    private function updateUser(Request $request, User $user, bool $staff, PublicImageStorage $images): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->where('school_id', $request->user()->school_id)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => [$staff ? 'required' : 'nullable', 'nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $photo = $data['photo'] ?? null;
        unset($data['photo']);
        if (! $staff) {
            unset($data['job_title']);
        }
        if ($photo) {
            $oldPath = $user->avatar_path;
            $data['avatar_path'] = $images->store($photo, 'avatars/'.$user->school_id);
            $images->deleteReplacement($oldPath, $data['avatar_path']);
        }
        $user->update($data);

        return back()->with('status', ucfirst($staff ? 'staff' : 'parent').' profile and photo saved.');
    }

    private function userView(User $user, string $type, string $backRoute, string $updateRoute, bool $canEdit, array $profileData = []): View
    {
        return view('profiles.person', compact('user', 'type', 'backRoute', 'updateRoute', 'canEdit') + ['person' => $user] + $profileData);
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless($student->school_id === request()->user()->school_id, 404);
        abort_unless(request()->user()->hasPermission('students.view') || request()->user()->hasPermission('students.manage'), 403);
    }

    private function authorizeUser(User $user, array $roles, string $permission): void
    {
        abort_unless($user->school_id === request()->user()->school_id && in_array($user->role, $roles, true), 404);
        abort_unless(request()->user()->hasPermission($permission), 403);
    }
}
