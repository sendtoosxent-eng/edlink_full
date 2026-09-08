<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ExamPaper;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\StudentSubjectSelectionService;
use App\Support\TeacherAcademicScope;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OfflineWorkspaceController extends Controller
{
    public function catalog(Request $request)
    {
        $user = $request->user();
        $term = $user->school->currentTerm();
        $classes = $user->hasPermission('attendance.daily')
            ? SchoolClass::where('school_id', $user->school_id)
                ->when(TeacherAcademicScope::isTeacher($user), fn ($q) => $q->whereIn('id', TeacherAcademicScope::classIds($user)))
                ->orderBy('name')->get(['id', 'name']) : collect();
        $papers = $term && $user->hasPermission('exams.marks')
            ? ExamPaper::with(['exam.schoolClass', 'subject'])->whereHas('exam', fn ($q) => $q->where('school_id', $user->school_id)->where('term_id', $term->id))
                ->get()->filter(fn ($paper) => TeacherAcademicScope::canEnterPaper($user, $paper->exam->school_class_id, $paper->subject_id, $term->id))
                ->map(fn ($paper) => ['id' => $paper->id, 'name' => $paper->exam->schoolClass->name.' · '.$paper->exam->name.' · '.$paper->subject->name])->values()
            : collect();

        return response()->json([
            'owner' => $user->id.':'.$user->school_id,
            'name' => $user->name,
            'school' => $user->school->name,
            'csrf' => csrf_token(),
            'today' => today()->toDateString(),
            'classes' => $classes,
            'papers' => $papers,
        ]);
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'kind' => 'required|in:attendance,marks',
            'target' => 'required|integer|min:1',
            'date' => 'required_if:kind,attendance|nullable|date_format:Y-m-d',
        ]);

        return response()->json($this->snapshot($request, $data));
    }

    private function context(Request $request, array $data): array
    {
        $user = $request->user();
        if ($data['kind'] === 'attendance') {
            abort_unless($user->hasPermission('attendance.daily'), 403);
            $class = SchoolClass::where('school_id', $user->school_id)->findOrFail($data['target']);
            abort_if(TeacherAcademicScope::isTeacher($user) && ! TeacherAcademicScope::classIds($user)->contains($class->id), 403);
            $term = $user->school->currentTerm();
            abort_unless($term && $term->isOpen(), 422, 'Open a term before preparing attendance.');
            $students = Student::where('school_id', $user->school_id)->where('school_class_id', $class->id)->where('status', 'active')->orderBy('name')->get();

            return compact('students', 'term', 'class') + ['title' => $class->name.' · '.$data['date']];
        }

        abort_unless($user->hasPermission('exams.marks'), 403);
        $paper = ExamPaper::with(['exam.term', 'exam.schoolClass', 'exam.stream', 'subject'])
            ->whereHas('exam', fn ($q) => $q->where('school_id', $user->school_id))->findOrFail($data['target']);
        abort_unless(TeacherAcademicScope::canEnterPaper($user, $paper->exam->school_class_id, $paper->subject_id, $paper->exam->term_id), 403);
        $term = $paper->exam->term;
        abort_unless($term->isOpen(), 422, 'This term is closed for marks entry.');
        $status = DB::table('exam_paper_submissions')->where('exam_paper_id', $paper->id)->value('status') ?? 'draft';
        abort_unless($status === 'draft', 409, 'This paper has been submitted or approved. Reopen it online before syncing.');
        $query = Student::where('school_id', $user->school_id)->where('school_class_id', $paper->exam->school_class_id)
            ->when($paper->exam->stream_id, fn ($q, $id) => $q->where('stream_id', $id))->where('status', 'active')->orderBy('name');
        $students = StudentSubjectSelectionService::constrainStudentsForSubject($query, $paper->exam->schoolClass, $term->id, $paper->subject_id)->get();

        return compact('students', 'term', 'paper') + ['title' => $paper->exam->schoolClass->name.' · '.$paper->exam->name.' · '.$paper->subject->name];
    }

    private function records(array $data, array $context)
    {
        return $data['kind'] === 'attendance'
            ? DB::table('attendance_records')->where('school_id', $context['term']->school_id)->where('session_key', 'daily')->whereDate('attendance_date', $data['date'])->whereIn('student_id', $context['students']->pluck('id'))
            : DB::table('exam_marks')->where('exam_paper_id', $context['paper']->id)->whereIn('student_id', $context['students']->pluck('id'));
    }

    private function value(?object $record, string $kind): mixed
    {
        return $kind === 'attendance' ? ($record?->status) : ($record?->score === null ? null : (float) $record->score);
    }

    private function snapshot(Request $request, array $data): array
    {
        $context = $this->context($request, $data);
        $records = $this->records($data, $context)->get()->keyBy('student_id');
        $rows = $context['students']->map(fn ($student) => [
            'id' => $student->id, 'name' => $student->name, 'admission_no' => $student->admission_no,
            'value' => $this->value($records->get($student->id), $data['kind']),
        ])->values()->all();
        $baseline = $context['students']->mapWithKeys(fn ($student) => [$student->id => [
            'id' => $records->get($student->id)?->id,
            'updated_at' => $records->get($student->id)?->updated_at,
            'value' => $this->value($records->get($student->id), $data['kind']),
        ]])->all();
        $owner = $request->user()->id.':'.$request->user()->school_id;

        return $data + [
            'key' => $data['kind'].':'.$data['target'].':'.($data['date'] ?? ''),
            'owner' => $owner, 'title' => $context['title'], 'rows' => $rows,
            'maximum' => isset($context['paper']) ? (float) $context['paper']->maximum_score : null,
            'downloaded_at' => now()->toISOString(),
            'token' => Crypt::encryptString(json_encode($data + ['owner' => $owner, 'term' => $context['term']->id, 'baseline' => $baseline], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)),
        ];
    }

    public function sync(Request $request)
    {
        $input = $request->validate([
            'token' => 'required|string', 'changes' => 'required|array|min:1|max:5000',
            'changes.*.id' => 'required|integer|distinct', 'changes.*.value' => 'present',
        ]);
        try {
            $data = json_decode(Crypt::decryptString($input['token']), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            abort(422, 'The downloaded record is invalid. Download it again.');
        }
        abort_unless($data['owner'] === $request->user()->id.':'.$request->user()->school_id, 403, 'Sign in to the account and school that downloaded this record.');

        DB::transaction(function () use ($request, $input, $data) {
            // Serialize offline syncs, including new rows, on their parent record.
            $model = $data['kind'] === 'attendance' ? SchoolClass::class : ExamPaper::class;
            $model::whereKey($data['target'])->lockForUpdate()->firstOrFail();
            $context = $this->context($request, $data);
            abort_unless($context['term']->id === $data['term'], 409, 'The active term changed. Download a new register.');
            $students = $context['students']->keyBy('id');
            $records = $this->records($data, $context)->lockForUpdate()->get()->keyBy('student_id');
            $field = $data['kind'] === 'attendance' ? 'status' : 'score';
            foreach ($input['changes'] as $change) {
                $id = $change['id'];
                abort_unless($students->has($id) && array_key_exists($id, $data['baseline']), 409, 'The class roster changed. Review your downloaded record.');
                $value = $change['value'];
                if ($data['kind'] === 'attendance') {
                    abort_unless(in_array($value, AttendanceRecord::STATUSES, true), 422, 'Choose a valid attendance status.');
                } else {
                    abort_unless($value === null || (is_numeric($value) && is_finite((float) $value) && $value >= 0 && $value <= $context['paper']->maximum_score), 422, 'A score is outside the paper maximum.');
                    $value = $value === null ? null : round((float) $value, 2);
                }
                $record = $records->get($id);
                // A retry after a lost response must not duplicate or undo an accepted value.
                if ($record && $this->value($record, $data['kind']) === $value) {
                    continue;
                }
                $base = $data['baseline'][$id];
                abort_unless(($record?->id === $base['id']) && ($record?->updated_at === $base['updated_at']) && ($this->value($record, $data['kind']) === $base['value']), 409, 'Online data changed for '.$students[$id]->name.'. Review the conflict before syncing.');
                $now = now();
                $values = $data['kind'] === 'attendance' ? [
                    'school_id' => $request->user()->school_id, 'term_id' => $context['term']->id,
                    'school_class_id' => $students[$id]->school_class_id, 'stream_id' => $students[$id]->stream_id,
                    'recorded_by' => $request->user()->id,
                ] : ['entered_by' => $request->user()->id];
                $values += [$field => $value, 'updated_at' => $now];
                if ($record) {
                    $updated = $this->records($data, $context)->where('id', $record->id)->where('updated_at', $base['updated_at'])->where($field, $base['value'])->update($values);
                    abort_unless($updated === 1, 409, 'Online data changed. Review before syncing.');
                } else {
                    $identity = $data['kind'] === 'attendance'
                        ? ['student_id' => $id, 'session_key' => 'daily', 'attendance_date' => $data['date'].' 00:00:00']
                        : ['student_id' => $id, 'exam_paper_id' => $data['target']];
                    $inserted = DB::table($data['kind'] === 'attendance' ? 'attendance_records' : 'exam_marks')->insertOrIgnore($identity + $values + ['created_at' => $now]);
                    abort_unless($inserted === 1, 409, 'Online data changed. Review before syncing.');
                }
            }
            AuditLog::record($request->user()->school_id, 'web.offline.'.$data['kind'].'.synced', null, ['target' => $data['target'], 'count' => count($input['changes'])]);
        });

        // Return only the download selectors, never a client-supplied baseline.
        return response()->json($this->snapshot($request, array_intersect_key($data, array_flip(['kind', 'target', 'date']))));
    }
}
