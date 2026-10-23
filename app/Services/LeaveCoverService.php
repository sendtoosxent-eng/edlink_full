<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\StaffLeave;
use App\Models\StaffLeaveHandover;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveCoverService
{
    public function duties(StaffLeave $leave): array
    {
        $termId = $leave->staff->school->currentTerm()?->id;
        $subjects = DB::table('staff_subjects')->where('school_id', $leave->school_id)->where('user_id', $leave->user_id)
            ->when($termId, fn ($q) => $q->where(fn ($scope) => $scope->where('term_id', $termId)->orWhereNull('term_id')))->get();
        $slots = DB::table('timetable_slots')->where('school_id', $leave->school_id)->where('user_id', $leave->user_id)
            ->when($termId, fn ($q) => $q->where('term_id', $termId))->get();
        $classes = DB::table('school_classes')->where('school_id', $leave->school_id)->where('class_teacher_user_id', $leave->user_id)->pluck('id');

        return compact('subjects', 'slots', 'classes');
    }

    public function approve(StaffLeave $leave, ?int $replacementId, User $approver, bool $reassign = false): void
    {
        abort_unless($approver->school_id === $leave->school_id && $approver->hasPermission('staff.leaves'), 403);
        DB::transaction(function () use ($leave, $replacementId, $approver, $reassign): void {
            $leave = StaffLeave::where('school_id', $approver->school_id)->lockForUpdate()->findOrFail($leave->id);
            if ((! $reassign && $leave->status !== 'pending') || ($reassign && ($leave->status !== 'approved' || $leave->returned_at || $leave->ends_on->lt(today()) || ! $leave->handover?->temporary))) throw ValidationException::withMessages(['replacementStaffId' => 'This request cannot be approved or reassigned in its current state.']);
            $previousReplacement = $leave->handover?->to_user_id;
            $coverStart = $reassign && $leave->starts_on->lt(today()) ? today() : $leave->starts_on;
            // Serialize competing decisions involving either teacher.
            $staff = User::where('school_id', $leave->school_id)->whereIn('id', array_filter([$leave->user_id, $replacementId]))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $duties = $this->duties($leave);
            if (StaffLeave::overlapping($leave->starts_on, $leave->ends_on)->where('school_id', $leave->school_id)->where('user_id', $leave->user_id)->where('id', '!=', $leave->id)->exists()) {
                throw ValidationException::withMessages(['replacementStaffId' => 'This staff member already has approved leave for these dates.']);
            }
            $covering = StaffLeaveHandover::where('school_id', $leave->school_id)->where('to_user_id', $leave->user_id)->where('temporary', true)
                ->whereHas('leave', fn ($q) => $q->overlapping($leave->starts_on, $leave->ends_on))->exists();
            if ($covering) throw ValidationException::withMessages(['replacementStaffId' => 'This staff member is already covering another leave during these dates. Arrange that cover before approving their absence.']);
            $hasDuties = $duties['subjects']->isNotEmpty() || $duties['slots']->isNotEmpty() || $duties['classes']->isNotEmpty();
            if ($hasDuties) {
                $replacement = $staff->get($replacementId);
                if (! $replacement || $replacement->id === $leave->user_id || ! in_array($replacement->role, ['teacher', 'admin', 'academic_admin'], true) || ! $replacement->availableBetween($coverStart, $leave->ends_on)) {
                    throw ValidationException::withMessages(['replacementStaffId' => 'Choose another active teacher or academic administrator who is available throughout the leave.']);
                }
                $coverSlots = StaffLeaveHandover::where('school_id', $leave->school_id)->where('to_user_id', $replacement->id)->where('temporary', true)
                    ->whereHas('leave', fn ($q) => $q->overlapping($leave->starts_on, $leave->ends_on))->get()->flatMap(fn ($cover) => $cover->timetable_slots ?? []);
                $ownSlots = DB::table('timetable_slots')->where('school_id', $leave->school_id)->where('user_id', $replacement->id)->get();
                foreach ($duties['slots'] as $slot) {
                    $firstLessonDate = $coverStart->copy();
                    if ($firstLessonDate->format('l') !== $slot->day_of_week) $firstLessonDate->next($slot->day_of_week);
                    if ($firstLessonDate->gt($leave->ends_on)) continue;
                    $conflict = $ownSlots->concat($coverSlots)->contains(function ($other) use ($slot) {
                        $other = (object) $other;
                        return (int) $other->term_id === (int) $slot->term_id && $other->day_of_week === $slot->day_of_week
                            && $other->starts_at < $slot->ends_at && $other->ends_at > $slot->starts_at;
                    });
                    if ($conflict) throw ValidationException::withMessages(['replacementStaffId' => 'The replacement has an overlapping lesson on '.$slot->day_of_week.' at '.substr($slot->starts_at, 0, 5).'.']);
                }
                StaffLeaveHandover::updateOrCreate(['staff_leave_id' => $leave->id], [
                    'school_id' => $leave->school_id, 'from_user_id' => $leave->user_id,
                    'to_user_id' => $replacement->id, 'class_ids' => $duties['classes']->all(),
                    'subject_assignments' => $duties['subjects']->toArray(), 'timetable_slots' => $duties['slots']->toArray(),
                    'temporary' => true, 'assigned_by' => $approver->id,
                ]);
                DB::table('school_notifications')->insert(['school_id' => $leave->school_id, 'user_id' => $replacement->id, 'title' => 'Temporary teaching cover assigned', 'message' => 'You are covering '.$leave->staff->name.' from '.$leave->starts_on->format('d M Y').' through '.$leave->ends_on->format('d M Y').'. Covered classes, subjects and reminders are available during those dates.', 'type' => 'info', 'created_at' => now(), 'updated_at' => now()]);
            }
            $leave->update(['status' => 'approved', 'approved_by' => $approver->id]);
            AuditLog::record($leave->school_id, $reassign ? 'leave.cover_reassigned' : 'leave.approved', $leave, ['previous_replacement_user_id' => $previousReplacement, 'replacement_user_id' => $hasDuties ? $replacementId : null, 'temporary' => true]);
        });
    }
}
