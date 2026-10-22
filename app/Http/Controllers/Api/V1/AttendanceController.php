<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AttendanceStoreRequest;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Stream;
use App\Services\StudentActivityNotifier;
use App\Support\MobileAccess;
use App\Support\SubjectAttendanceRegisters;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends ApiController
{
    public function performance(Request $request)
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user) && $user->hasPermission('attendance.daily'), 403);
        $data = $request->validate([
            'from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from'],
            'school_class_id' => ['required', 'integer'],
            'stream_id' => ['nullable', 'integer', Rule::exists('streams', 'id')->where('school_id', $user->school_id)->where('school_class_id', $request->integer('school_class_id'))],
        ]);
        abort_unless(TeacherAcademicScope::classIds($user)->contains((int) $data['school_class_id']), 403);
        $component = \Livewire\Livewire::new(\App\Livewire\Attendance::class);
        $component->mount();
        $component->schoolClassId = (string) $data['school_class_id'];
        $component->streamId = (string) ($data['stream_id'] ?? '');
        $component->reportFrom = $data['from'];
        $component->reportTo = $data['to'];

        return $request->routeIs('api.mobile.attendance.performance.csv')
            ? $component->exportPerformance()
            : $this->ok($component->performanceRows());
    }

    public function registers(Request $request)
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user), 403);
        $data = $request->validate(['attendance_date' => ['required', 'date']]);
        $daily = collect();
        if ($user->hasPermission('attendance.daily')) {
            $classes = DB::table('school_classes')->where('school_id', $user->school_id)->whereIn('id', TeacherAcademicScope::classIds($user))->orderBy('name')->get();
            $daily = $classes->map(fn ($class) => ['school_class_id' => $class->id, 'class_name' => $class->name, 'subject_id' => null, 'subject_name' => 'Daily register', 'attendance_type' => 'daily', 'session_key' => 'daily', 'stream_id' => null]);
        }
        $subjects = $user->hasPermission('attendance.subject')
            ? SubjectAttendanceRegisters::forUser($user, $data['attendance_date'])->map(fn ($slot) => [
                'school_class_id' => (int) $slot->school_class_id, 'class_name' => $slot->class_name,
                'subject_id' => (int) $slot->subject_id, 'subject_name' => $slot->subject_name,
                'attendance_type' => 'subject', 'session_key' => SubjectAttendanceRegisters::sessionKey($slot),
                'stream_id' => $slot->stream_id, 'stream_name' => $slot->stream_name,
                'starts_at' => $slot->starts_at, 'ends_at' => $slot->ends_at,
            ]) : collect();
        $registers = $daily->concat($subjects)->values();

        return $this->ok(['registers' => $registers, 'streams' => Stream::where('school_id', $user->school_id)->whereIn('school_class_id', $daily->pluck('school_class_id'))->orderBy('name')->get(['id', 'name', 'school_class_id']), 'can_record' => (bool) $user->school->currentTerm()?->isOpen()]);
    }

    private function registerContext(Request $request): array
    {
        $user = $request->user();
        $data = $request->validate([
            'school_class_id' => ['required', 'integer'], 'subject_id' => ['nullable', 'integer'],
            'attendance_date' => ['required', 'date'], 'session_key' => ['required', 'string', 'max:80'],
            'stream_id' => ['nullable', 'integer', Rule::exists('streams', 'id')->where('school_id', $user->school_id)->where('school_class_id', $request->integer('school_class_id'))],
        ]);
        $subjectId = $data['subject_id'] ?? null;
        abort_unless($user->hasPermission($subjectId ? 'attendance.subject' : 'attendance.daily'), 403);
        if ($subjectId && (str_contains($data['session_key'], ':class:') || str_contains($data['session_key'], ':slot:'))) {
            $slot = SubjectAttendanceRegisters::forUser($user, $data['attendance_date'])->first(fn ($slot) => SubjectAttendanceRegisters::sessionKey($slot) === $data['session_key'] && (int) $slot->school_class_id === (int) $data['school_class_id'] && (int) $slot->subject_id === (int) $subjectId);
            abort_unless($slot, 403, 'This lesson register is not assigned to you.');

            return [SubjectAttendanceRegisters::studentsFor($user, $slot), $slot->stream_id, $slot->starts_at];
        }
        // Keep existing app versions working while new clients use lesson registers.
        abort_unless($subjectId ? $data['session_key'] !== 'daily' : $data['session_key'] === 'daily', 422, 'Choose the correct register session.');
        $students = MobileAccess::teacherStudentQuery($user, (int) $data['school_class_id'], $subjectId, null, $data['stream_id'] ?? null)->orderBy('name')->get();

        return [$students, $data['stream_id'] ?? null, null];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (in_array($user->role, ['student', 'parent'], true)) {
            $student = MobileAccess::student($user, $request->integer('student_id') ?: null);

            return $this->ok(AttendanceRecord::where('school_id', $user->school_id)->where('student_id', $student->id)->latest('attendance_date')->paginate(30));
        }
        [$students] = $this->registerContext($request);
        $records = AttendanceRecord::where('school_id', $user->school_id)->whereIn('student_id', $students->pluck('id'))
            ->whereDate('attendance_date', $request->date('attendance_date') ?? today())->where('session_key', $request->string('session_key', 'daily'))->get()->keyBy('student_id');

        return $this->ok($students->map(fn ($student) => ['student' => ['id' => $student->id, 'name' => $student->name, 'admission_no' => $student->admission_no, 'photo_url' => $student->photoUrl()], 'status' => $records->get($student->id)?->status ?? 'present', 'note' => $records->get($student->id)?->status === 'late' ? preg_replace('/[^0-9]/', '', (string) $records->get($student->id)?->note) : $records->get($student->id)?->note]),
            ['version' => $records->max('updated_at')?->toISOString()]);
    }

    public function store(AttendanceStoreRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        abort_unless($user->hasPermission(! empty($data['subject_id']) ? 'attendance.subject' : 'attendance.daily'), 403);
        abort_unless(! empty($data['subject_id']) ? $data['session_key'] !== 'daily' : $data['session_key'] === 'daily', 422, 'Choose the correct register session.');
        $term = $user->school->currentTerm();
        abort_unless($term && $term->status === 'open', 422, 'Open a term before recording attendance.');
        [$students, $streamId, $lessonTime] = $this->registerContext($request);
        $allowed = $students->pluck('id');
        abort_unless(collect($data['records'])->pluck('student_id')->diff($allowed)->isEmpty(), 403);
        $existing = AttendanceRecord::where('school_id', $user->school_id)->whereIn('student_id', $allowed)
            ->whereDate('attendance_date', $data['attendance_date'])->where('session_key', $data['session_key'])->max('updated_at');
        if (! empty($data['base_version']) && $existing && $existing > $data['base_version']) {
            return response()->json(['message' => 'Attendance changed on another device.', 'code' => 'conflict'], 409);
        }
        DB::transaction(function () use ($data, $user, $term, $students, $streamId, $lessonTime) {
            foreach ($data['records'] as $record) {
                $saved = AttendanceRecord::updateOrCreate(
                    ['student_id' => $record['student_id'], 'attendance_date' => $data['attendance_date'], 'session_key' => $data['session_key']],
                    ['school_id' => $user->school_id, 'term_id' => $term->id, 'school_class_id' => $data['school_class_id'], 'subject_id' => $data['subject_id'] ?? null, 'stream_id' => $streamId ?? $students->firstWhere('id', $record['student_id'])?->stream_id, 'lesson_time' => $lessonTime, 'status' => $record['status'], 'note' => $record['status'] === 'present' ? null : ($record['status'] === 'late' ? 'Late by '.$record['note'].' minutes' : $record['note']), 'recorded_by' => $user->id]
                );
                app(StudentActivityNotifier::class)->attendance($saved);
            }
        });
        AuditLog::record($user->school_id, 'mobile.attendance.saved', null, ['class_id' => $data['school_class_id'], 'count' => count($data['records'])]);

        return $this->ok(['saved' => count($data['records']), 'version' => now()->toISOString()]);
    }
}
