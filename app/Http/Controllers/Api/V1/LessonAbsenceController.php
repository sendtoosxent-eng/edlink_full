<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AuditLog;
use App\Models\LessonAbsenceReport;
use App\Models\User;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LessonAbsenceController extends ApiController
{
    public function __invoke(Request $request, int $lesson)
    {
        $user = $request->user();
        abort_unless($user->role === 'teacher', 403);
        $term = $user->school->currentTerm();
        if (! $term?->isOpen()) {
            throw ValidationException::withMessages(['lesson' => 'Absences can only be reported during an open term.']);
        }
        $slot = DB::table('timetable_slots')
            ->leftJoin('subjects', 'subjects.id', '=', 'timetable_slots.subject_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'timetable_slots.school_class_id')
            ->leftJoin('streams', 'streams.id', '=', 'timetable_slots.stream_id')
            ->where('timetable_slots.id', $lesson)->where('timetable_slots.school_id', $user->school_id)
            ->where('timetable_slots.term_id', $term->id)
            ->whereIn('timetable_slots.user_id', TeacherAcademicScope::teachingOwnerIds($user, $term->id))
            ->select('timetable_slots.*', 'subjects.name as subject', 'school_classes.name as class_name', 'streams.name as stream_name')
            ->first();
        abort_unless($slot, 404);
        if ($slot->day_of_week !== now()->format('l') || $slot->ends_at <= now()->format('H:i:s')) {
            throw ValidationException::withMessages(['lesson' => 'Choose an upcoming or ongoing lesson from today’s timetable.']);
        }

        $report = DB::transaction(function () use ($user, $slot): LessonAbsenceReport {
            // Serialize retries so one lesson report creates one set of notices.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = LessonAbsenceReport::where('school_id', $user->school_id)->where('user_id', $user->id)
                ->where('timetable_slot_id', $slot->id)->whereDate('lesson_date', today())->first();
            if ($existing) return $existing;

            $admins = User::where('school_id', $user->school_id)->whereKeyNot($user->id)
                ->whereIn('role', ['admin', 'superadmin', 'academic_admin'])
                ->where(fn ($query) => $query->whereNull('employment_status')->orWhere('employment_status', '!=', 'inactive'))->pluck('id');
            if ($admins->isEmpty()) {
                throw ValidationException::withMessages(['lesson' => 'No active school administrator is available to receive this report. Please contact your school directly.']);
            }
            $report = LessonAbsenceReport::create([
                'school_id' => $user->school_id, 'user_id' => $user->id, 'timetable_slot_id' => $slot->id,
                'lesson_date' => today(), 'lesson_details' => [
                    'subject' => $slot->subject ?: $slot->label ?: 'Lesson', 'class_name' => $slot->class_name,
                    'stream_name' => $slot->stream_name, 'starts_at' => $slot->starts_at, 'ends_at' => $slot->ends_at,
                ],
            ]);
            $subject = $slot->subject ?: $slot->label ?: 'Lesson';
            $class = trim(($slot->class_name ?: 'Class').' '.($slot->stream_name ?? ''));
            $message = $user->name.' will not be able to attend '.$subject.' for '.$class.' on '.today()->format('d M Y')
                .' from '.substr($slot->starts_at, 0, 5).' to '.substr($slot->ends_at, 0, 5).'. Please arrange lesson cover.';
            foreach ($admins as $adminId) {
                DB::table('school_notifications')->insert([
                    'school_id' => $user->school_id, 'user_id' => $adminId,
                    'title' => 'Teacher lesson absence reported', 'message' => $message,
                    'type' => 'warning', 'category' => 'lesson_absence', 'event_key' => 'lesson-absence:'.$report->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            AuditLog::record($user->school_id, 'lesson.absence.reported', $report, ['lesson_id' => $slot->id, 'admin_count' => $admins->count()]);

            return $report;
        });

        return $this->ok(['id' => $report->id, 'reported_at' => $report->created_at->toIso8601String(), 'message' => 'Your school administrators have been notified about this lesson.']);
    }
}
