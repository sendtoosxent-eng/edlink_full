<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkSubmission;
use App\Models\SchoolSetting;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentActivityNotifier
{
    public const SETTINGS = [
        'notification_daily_attendance_enabled',
        'notification_subject_attendance_enabled',
        'notification_homework_enabled',
        'notification_homework_feedback_enabled',
        'notification_results_enabled',
    ];

    public function attendance(AttendanceRecord $record): void
    {
        $isDaily = $record->session_key === 'daily';
        $setting = $isDaily ? self::SETTINGS[0] : self::SETTINGS[1];
        if (! $this->enabled($record->school_id, $setting)) {
            return;
        }

        $student = Student::find($record->student_id);
        if (! $student) {
            return;
        }

        $subject = $record->subject_id ? DB::table('subjects')->where('id', $record->subject_id)->value('name') : null;
        $label = $isDaily ? 'Daily attendance' : ($subject ?: 'Subject attendance');
        $status = ucfirst($record->status);
        $date = $record->attendance_date->format('d M Y');
        $this->send($student, $isDaily ? 'daily_attendance' : 'subject_attendance', $label.': '.$status,
            "{$student->name} was marked {$record->status} for {$label} on {$date}.",
            'attendance:'.$record->id, $record->status === 'absent' ? 'warning' : 'success');
    }

    public function homework(HomeworkAssignment $assignment): void
    {
        if (! $this->enabled($assignment->school_id, self::SETTINGS[2])) {
            return;
        }
        $assignment->loadMissing('subject:id,name');
        $students = Student::where('school_id', $assignment->school_id)->where('status', 'active')
            ->where('school_class_id', $assignment->school_class_id)
            ->when($assignment->stream_id, fn ($query) => $query->where('stream_id', $assignment->stream_id))->get();
        foreach ($students as $student) {
            if (! StudentSubjectSelectionService::studentTakesSubject($student, $assignment->term_id, $assignment->subject_id)) {
                continue;
            }
            $this->send($student, 'homework', 'New homework: '.$assignment->title,
                ($assignment->subject?->name ?: 'Class').' homework for '.$student->name.' is due '.$assignment->due_at->format('d M Y, H:i').'.',
                'homework:'.$assignment->id, 'info');
        }
    }

    public function homeworkFeedback(HomeworkSubmission $submission): void
    {
        $submission->loadMissing('assignment.subject:id,name', 'student:id,school_id,name');
        $student = $submission->student;
        if (! $student || ! $this->enabled($student->school_id, self::SETTINGS[3])) {
            return;
        }
        $score = $submission->score === null ? '' : ' Score: '.$submission->score.'/'.$submission->assignment->maximum_score.'.';
        $this->send($student, 'homework_feedback', 'Homework feedback ready',
            $submission->assignment->title.' for '.$student->name.' has been reviewed.'.$score,
            'homework-feedback:'.$submission->id, 'success');
    }

    public function results(Exam $exam): void
    {
        if (! $this->enabled($exam->school_id, self::SETTINGS[4])) {
            return;
        }
        $students = Student::where('school_id', $exam->school_id)->where('status', 'active')
            ->where('school_class_id', $exam->school_class_id)
            ->when($exam->stream_id, fn ($query) => $query->where('stream_id', $exam->stream_id))->get();
        foreach ($students as $student) {
            $this->send($student, 'results', 'Results are out: '.$exam->name,
                $exam->name.' results for '.$student->name.' are now available in Edlink.',
                'results:'.$exam->id, 'success');
        }
    }

    private function enabled(int $schoolId, string $key): bool
    {
        return SchoolSetting::where(['school_id' => $schoolId, 'key' => $key])->value('value') !== 'disabled';
    }

    private function send(Student $student, string $category, string $title, string $message, string $eventKey, string $type): void
    {
        foreach ($this->recipientIds($student) as $userId) {
            DB::table('school_notifications')->updateOrInsert(
                ['school_id' => $student->school_id, 'user_id' => $userId, 'event_key' => $eventKey],
                ['student_id' => $student->id, 'title' => $title, 'message' => $message, 'type' => $type,
                    'category' => $category, 'read_at' => null, 'created_at' => now(), 'updated_at' => now()],
            );
            DB::table('school_notification_reads')->where('school_notification_id', function ($query) use ($student, $userId, $eventKey): void {
                $query->select('id')->from('school_notifications')->where('school_id', $student->school_id)
                    ->where('user_id', $userId)->where('event_key', $eventKey)->limit(1);
            })->where('user_id', $userId)->delete();
        }
    }

    private function recipientIds(Student $student): Collection
    {
        return DB::table('portal_user_students')->join('users', 'users.id', '=', 'portal_user_students.user_id')
            ->where('portal_user_students.school_id', $student->school_id)->where('portal_user_students.student_id', $student->id)
            ->whereIn('users.role', ['parent', 'student'])->where('users.school_id', $student->school_id)
            ->pluck('users.id')->unique()->values();
    }
}
