<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Announcement;
use App\Models\Exam;
use App\Models\HomeworkAssignment;
use App\Support\MobileAccess;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileDataController extends ApiController
{
    private function student(Request $request) {
        return in_array($request->user()->role, ['student', 'parent'], true)
            ? MobileAccess::student($request->user(), $request->integer('student_id') ?: null) : null;
    }

    public function dashboard(Request $request)
    {
        $user = $request->user()->load('school');
        $student = $this->student($request);
        $term = $user->school->currentTerm();
        $today = now()->format('l');
        $slots = $this->slotQuery($request, $student)->where('day_of_week', $today)->orderBy('starts_at')->get();
        $homework = $this->homeworkQuery($request, $student)->orderBy('due_at')->limit(4)->get();
        $attendance = $student ? DB::table('attendance_records')->where('school_id', $user->school_id)
            ->where('student_id', $student->id)->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status') : collect();

        return $this->ok([
            'date' => today()->toDateString(),
            'student' => $student ? $this->studentPayload($student) : null,
            'today_timetable' => $slots,
            'next_lesson' => $slots->first(fn ($slot) => $slot->ends_at > now()->format('H:i:s')),
            'homework' => $homework,
            'attendance' => $attendance,
            'events' => DB::table('school_events')->where('school_id', $user->school_id)->whereDate('event_date', '>=', today())->orderBy('event_date')->limit(4)->get(),
        ], ['generated_at' => now()->toISOString()]);
    }

    public function timetable(Request $request)
    {
        return $this->ok($this->slotQuery($request, $this->student($request))
            ->orderByRaw("CASE day_of_week WHEN 'Monday' THEN 1 WHEN 'Tuesday' THEN 2 WHEN 'Wednesday' THEN 3 WHEN 'Thursday' THEN 4 WHEN 'Friday' THEN 5 WHEN 'Saturday' THEN 6 ELSE 7 END")
            ->orderBy('starts_at')->get());
    }

    public function events(Request $request)
    {
        return $this->ok(DB::table('school_events')->where('school_id', $request->user()->school_id)
            ->whereDate('event_date', '>=', today())->orderBy('event_date')->paginate(20));
    }

    public function announcements(Request $request)
    {
        $role = $request->user()->role;
        return $this->ok(Announcement::where('school_id', $request->user()->school_id)->whereNotNull('sent_at')
            ->whereIn('target_audience', ['all', $role, $role.'s'])->latest('sent_at')->paginate(20));
    }

    public function children(Request $request)
    {
        abort_unless($request->user()->role === 'parent', 403);
        return $this->ok($request->user()->portalStudents()->where('students.school_id', $request->user()->school_id)
            ->with(['schoolClass:id,name', 'stream:id,name'])->get()->map(fn ($student) => $this->studentPayload($student)));
    }

    public function results(Request $request)
    {
        $student = MobileAccess::student($request->user(), $request->integer('student_id') ?: null);
        $exams = Exam::query()->where('school_id', $request->user()->school_id)->where('school_class_id', $student->school_class_id)
            ->whereNotNull('published_at')->with(['term:id,name', 'papers.subject:id,name,code'])
            ->latest('published_at')->get()->map(function ($exam) use ($student) {
                $marks = DB::table('exam_marks')->where('student_id', $student->id)->whereIn('exam_paper_id', $exam->papers->pluck('id'))->get()->keyBy('exam_paper_id');
                return ['id'=>$exam->id,'name'=>$exam->name,'term'=>$exam->term?->name,'published_at'=>$exam->published_at,
                    'papers'=>$exam->papers->map(fn($paper)=>['id'=>$paper->id,'subject'=>$paper->subject?->name,'score'=>$marks->get($paper->id)?->score,'maximum_score'=>$paper->maximum_score])];
            });
        return $this->ok($exams);
    }

    public function activities(Request $request)
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user), 403);
        $houses = DB::table('student_houses')->where('school_id',$user->school_id)->where('patron_user_id',$user->id)->get();
        $clubs = DB::table('student_clubs')->where('school_id',$user->school_id)->where('patron_user_id',$user->id)->get();
        return $this->ok(['houses'=>$houses,'clubs'=>$clubs]);
    }

    public function teachingAssignments(Request $request)
    {
        $user=$request->user(); abort_unless(TeacherAcademicScope::isTeacher($user),403);
        $termId=$user->school->currentTerm()?->id;
        $assigned=DB::table('staff_subjects')->join('school_classes','school_classes.id','=','staff_subjects.school_class_id')->join('subjects','subjects.id','=','staff_subjects.subject_id')
            ->where('staff_subjects.school_id',$user->school_id)->where('staff_subjects.user_id',$user->id)
            ->when($termId,fn($q)=>$q->where(fn($x)=>$x->where('staff_subjects.term_id',$termId)->orWhereNull('staff_subjects.term_id')))
            ->get(['school_classes.id as school_class_id','school_classes.name as class_name','subjects.id as subject_id','subjects.name as subject_name']);
        foreach(TeacherAcademicScope::classIds($user) as $classId){
            $className=DB::table('school_classes')->where('school_id',$user->school_id)->where('id',$classId)->value('name');
            foreach(DB::table('subjects')->where('school_id',$user->school_id)->get(['id','name']) as $subject) $assigned->push((object)['school_class_id'=>$classId,'class_name'=>$className,'subject_id'=>$subject->id,'subject_name'=>$subject->name]);
        }
        return $this->ok($assigned->unique(fn($a)=>$a->school_class_id.'-'.$a->subject_id)->values());
    }

    private function slotQuery(Request $request, $student)
    {
        $user = $request->user();
        $query = DB::table('timetable_slots')->leftJoin('subjects','subjects.id','=','timetable_slots.subject_id')
            ->leftJoin('school_classes','school_classes.id','=','timetable_slots.school_class_id')
            ->leftJoin('streams','streams.id','=','timetable_slots.stream_id')
            ->where('timetable_slots.school_id',$user->school_id)
            ->select(['timetable_slots.id','timetable_slots.school_class_id','timetable_slots.subject_id','day_of_week','starts_at','ends_at','label','subjects.name as subject','school_classes.name as class_name','streams.name as stream_name']);
        if ($student) $query->where('timetable_slots.school_class_id',$student->school_class_id)
            ->where(fn($q)=>$q->whereNull('timetable_slots.stream_id')->orWhere('timetable_slots.stream_id',$student->stream_id));
        else $query->where('timetable_slots.user_id',$user->id);
        return $query;
    }

    private function homeworkQuery(Request $request, $student)
    {
        $query = HomeworkAssignment::query()->where('school_id',$request->user()->school_id)->with(['subject:id,name','schoolClass:id,name']);
        if ($student) $query->whereNotNull('published_at')->where('school_class_id',$student->school_class_id)
            ->where(fn($q)=>$q->whereNull('stream_id')->orWhere('stream_id',$student->stream_id));
        else $query->where('teacher_id',$request->user()->id);
        return $query;
    }

    private function studentPayload($student): array
    {
        return ['id'=>$student->id,'name'=>$student->name,'admission_no'=>$student->admission_no,'photo_url'=>$student->photoUrl(),
            'class'=>$student->schoolClass?->name,'stream'=>$student->stream?->name];
    }
}
