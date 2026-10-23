<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\PublicImageStorage;
use App\Services\StudentProfileData;
use App\Services\StaffProfileData;
use App\Services\ParentProfileData;
use App\Services\StaffUploadService;
use App\Models\StaffDocument;
use Illuminate\Support\Facades\Storage;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonProfileController extends Controller
{
    public function student(Student $student): View
    {
        $this->authorizeStudent($student);

        return view('profiles.student', app(StudentProfileData::class)->build($student, request()->user(), request()->integer('term') ?: null));
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
            'admission_date' => ['nullable', 'date'],
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

    public function updateStudentStatus(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);
        abort_unless($request->user()->hasPermission('students.manage'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        DB::transaction(function () use ($student, $data): void {
            $record = Student::where('school_id', $student->school_id)->lockForUpdate()->findOrFail($student->id);
            if ($record->status === 'graduated') {
                throw ValidationException::withMessages(['status' => 'Graduates can only be restored from the Graduates & Alumni register.']);
            }
            $previous = $record->status;
            $record->update(['status' => $data['status']]);
            $term = $record->school->currentTerm();
            if ($term) {
                $record->enrolments()->where('school_id', $record->school_id)->where('term_id', $term->id)->update(['status' => $data['status']]);
            }
            if ($previous !== $data['status']) {
                AuditLog::record($record->school_id, 'student.status.updated', $record, ['previous_status' => $previous, 'status' => $data['status']]);
            }
        });
        return back()->with('status', $student->name.' marked as '.$data['status'].'.');
    }

    public function staff(User $user): View
    {
        $this->authorizeUser($user, ['admin', 'superadmin', 'teacher', 'bursar', 'registrar', 'academic_admin'], 'staff.directory');
        request()->validate(['period' => ['nullable', 'date_format:Y-m']]);
        return view('profiles.staff', app(StaffProfileData::class)->build($user, request()->user(), request()->integer('term') ?: null, request('period') ?: now()->format('Y-m')));
    }

    public function staffDocument(User $user, StaffDocument $document)
    {
        $this->authorizeUser($user, ['admin', 'superadmin', 'teacher', 'bursar', 'registrar', 'academic_admin'], 'staff.manage');
        abort_if(in_array($user->role, ['admin', 'superadmin'], true) && ! in_array(request()->user()->role, ['admin', 'superadmin'], true), 403);
        abort_unless($document->school_id === $user->school_id && $document->user_id === $user->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function parent(User $user): View
    {
        $this->authorizeUser($user, ['parent'], 'parents.manage');

        request()->validate(['term' => ['nullable', 'integer', 'min:1']]);

        return view('profiles.parent', app(ParentProfileData::class)->build($user, request()->user(), request()->integer('term') ?: null));
    }

    public function updateStaff(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user, ['admin', 'superadmin', 'teacher', 'bursar', 'registrar', 'academic_admin'], 'staff.manage');
        abort_if(in_array($user->role, ['admin', 'superadmin'], true) && ! in_array($request->user()->role, ['admin', 'superadmin'], true), 403);

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
            'studentIds.*' => ['integer', 'distinct', Rule::exists('students', 'id')->where('school_id', $request->user()->school_id)],
            'sync_learners' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        $photo = $data['photo'] ?? null;
        unset($data['photo'], $data['studentIds'], $data['sync_learners']);
        if (blank($data['password'] ?? null)) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        if ($photo) {
            $oldPath = $user->avatar_path;
            $data['avatar_path'] = app(PublicImageStorage::class)->store($photo, 'avatars/'.$user->school_id);
        }
        DB::transaction(function () use ($user, $data, $request): void {
            $user->update($data);
            if ($request->has('studentIds') || $request->boolean('sync_learners')) {
                $existing = $user->portalStudents()->wherePivot('school_id', $user->school_id)->get()->keyBy('id');
                $links = collect($request->input('studentIds', []))->mapWithKeys(fn ($id) => [(int) $id => [
                    'school_id' => $user->school_id, 'relationship' => $existing->get((int) $id)?->pivot->relationship ?: 'Parent',
                ]])->all();
                $user->portalStudents()->sync($links);
            }
            AuditLog::record($user->school_id, 'parent.profile.updated', $user);

        });
        if ($photo) app(PublicImageStorage::class)->deleteReplacement($oldPath, $data['avatar_path']);
        return back()->with('status', 'Parent profile saved.');
    }

    private function updateUser(Request $request, User $user, bool $staff, PublicImageStorage $images): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->where('school_id', $request->user()->school_id)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => [$staff ? 'required' : 'nullable', 'nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            ...($staff ? [
                ...StaffUploadService::rules(),
                'location' => ['nullable', 'string', 'max:500'],
                'joined_at' => ['nullable', 'date'],
                'contract_type' => ['nullable', Rule::in(['permanent', 'contract', 'part_time', 'volunteer'])],
                'probation_ends_at' => ['nullable', 'date', 'after_or_equal:'.($request->input('joined_at') ?: $user->joined_at?->toDateString() ?: '1900-01-01')],
                'base_salary' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999.99'],
                'emergency_contact_name' => ['nullable', 'string', 'max:255'], 'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
                'national_id' => ['nullable', 'string', 'max:100'], 'bank_name' => ['nullable', 'string', 'max:255'],
                'bank_account_name' => ['nullable', 'string', 'max:255'], 'bank_account_number' => ['nullable', 'string', 'max:100'],
            ] : []),
        ]);
        $photo = $data['photo'] ?? null;
        $documents = $data['qualification_documents'] ?? [];
        unset($data['photo'], $data['qualification_documents']);
        if ($staff) {
            $oldPath = $user->avatar_path;
            app(StaffUploadService::class)->persist($user->school_id, $photo, $documents, function ($photoPath, $rows) use ($user, $data): void {
                $user->update($data + ($photoPath ? ['avatar_path' => $photoPath] : []));
                $user->staffDocuments()->createMany($rows);
                AuditLog::record($user->school_id, 'staff.profile.updated', $user, ['documents_added' => count($rows)]);
            });
            if ($photo) $images->deleteReplacement($oldPath, $user->avatar_path);

            return back()->with('status', 'Staff profile, photo and qualifications saved.');
        }
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
        $user = request()->user();
        if (TeacherAcademicScope::isTeacher($user) && ! $user->hasPermission('students.manage')) {
            abort_unless(TeacherAcademicScope::scopeStudents(Student::query(), $user, $user->school->currentTerm()?->id)->whereKey($student->id)->exists(), 404);
            return;
        }
        abort_unless($user->hasPermission('students.view') || $user->hasPermission('students.manage'), 403);
    }

    private function authorizeUser(User $user, array $roles, string $permission): void
    {
        abort_unless($user->school_id === request()->user()->school_id && in_array($user->role, $roles, true), 404);
        abort_unless(request()->user()->hasPermission($permission), 403);
    }
}
