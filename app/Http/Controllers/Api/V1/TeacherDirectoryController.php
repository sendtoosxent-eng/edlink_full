<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Student;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;

class TeacherDirectoryController extends ApiController
{
    private function student(Request $request, int $id): Student
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user) && TeacherAcademicScope::canViewStudentDirectory($user), 403);
        return TeacherAcademicScope::scopeStudents(Student::where('school_id', $user->school_id), $user, $user->school->currentTerm()?->id)->findOrFail($id);
    }

    public function show(Request $request, int $student)
    {
        $student = $this->student($request, $student);
        $fields = ['name', 'admission_no', 'date_of_birth', 'gender', 'nationality', 'religion', 'blood_group', 'home_address', 'medical_notes'];
        return $this->ok(['student' => collect($fields)->mapWithKeys(fn ($field) => [$field => $field === 'date_of_birth' ? $student->date_of_birth?->toDateString() : $student->{$field}]), 'photo_url' => $student->photoUrl(), 'can_edit' => $request->user()->hasPermission('students.manage')]);
    }

    public function update(Request $request, int $student)
    {
        $student = $this->student($request, $student);
        app(\App\Http\Controllers\PersonProfileController::class)->updateStudent($request, $student, app(\App\Services\PublicImageStorage::class));
        return $this->ok(['saved' => true]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user) && TeacherAcademicScope::canViewStudentDirectory($user), 403);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'class_id' => ['nullable', 'integer']]);
        $query = TeacherAcademicScope::scopeStudents(Student::where('school_id', $user->school_id), $user, $user->school->currentTerm()?->id);
        $query->when($data['class_id'] ?? null, fn ($q, $id) => $q->where('school_class_id', $id));
        $query->when($data['search'] ?? null, fn ($q, $search) => $q->where(fn ($match) => $match->where('name', 'like', '%'.$search.'%')->orWhere('admission_no', 'like', '%'.$search.'%')));
        return $this->ok($query->with(['schoolClass:id,name', 'stream:id,name'])->orderBy('name')->paginate(30, ['id', 'name', 'admission_no', 'school_class_id', 'stream_id', 'status']));
    }
}
