<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\SchoolEvent;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class StaffWorkbench extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_if(in_array($user->role, ['parent', 'student'], true), 403);
        $school = $user->school;
        $term = $school->currentTerm();
        $default = match ($user->role) { 'teacher' => ['attendance', 'academics', 'exams'], 'bursar' => ['finance', 'reports'], default => ['students', 'finance', 'attendance', 'academics', 'exams', 'staff', 'parents', 'reports', 'settings'] };
        $permissions = $user->designation_id ? ($user->designation?->permissions ?? []) : $default;
        $modules = ['students' => ['Students & admissions', 'students.index'], 'finance' => ['Finance', 'fee-payments.index'], 'attendance' => ['Attendance', 'attendance.index'], 'academics' => ['Academics', 'classes.index'], 'exams' => ['Exams & results', 'exams.marks'], 'staff' => ['Staff & leave', 'staff.index'], 'parents' => ['Parents & communication', 'parents.index'], 'reports' => ['Reports', 'reports.index'], 'settings' => ['Settings', 'settings.index']];
        $attendanceToday = $term ? AttendanceRecord::where('school_id', $school->id)->where('term_id', $term->id)->whereDate('attendance_date', today())->count() : 0;
        $events = SchoolEvent::where('school_id', $school->id)->whereDate('event_date', '>=', today())->orderBy('event_date')->take(4)->get();
        return view('livewire.staff-workbench', compact('school', 'term', 'permissions', 'modules', 'attendanceToday', 'events'), ['pageTitle' => 'Staff Workbench']);
    }
}
