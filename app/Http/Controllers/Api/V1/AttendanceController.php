<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AttendanceStoreRequest;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Support\MobileAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (in_array($user->role, ['student','parent'], true)) {
            $student = MobileAccess::student($user, $request->integer('student_id') ?: null);
            return $this->ok(AttendanceRecord::where('school_id',$user->school_id)->where('student_id',$student->id)->latest('attendance_date')->paginate(30));
        }
        $classId = $request->integer('school_class_id');
        abort_unless($classId, 422);
        $students = MobileAccess::teacherStudentQuery($user,$classId,$request->integer('subject_id') ?: null)->orderBy('name')->get(['id','name','admission_no']);
        $records = AttendanceRecord::where('school_id',$user->school_id)->whereIn('student_id',$students->pluck('id'))
            ->whereDate('attendance_date',$request->date('attendance_date') ?? today())->where('session_key',$request->string('session_key','daily'))->get()->keyBy('student_id');
        return $this->ok($students->map(fn($student)=>['student'=>$student,'status'=>$records->get($student->id)?->status ?? 'present']),
            ['version'=>$records->max('updated_at')?->toISOString()]);
    }

    public function store(AttendanceStoreRequest $request)
    {
        $user=$request->user(); $data=$request->validated();
        $term = $user->school->currentTerm();
        abort_unless($term, 422, 'Open a term before recording attendance.');
        $allowed=MobileAccess::teacherStudentQuery($user,$data['school_class_id'],$data['subject_id'] ?? null)->pluck('id');
        abort_unless(collect($data['records'])->pluck('student_id')->diff($allowed)->isEmpty(),403);
        $existing=AttendanceRecord::where('school_id',$user->school_id)->whereIn('student_id',$allowed)
            ->whereDate('attendance_date',$data['attendance_date'])->where('session_key',$data['session_key'])->max('updated_at');
        if (!empty($data['base_version']) && $existing && $existing > $data['base_version']) return response()->json(['message'=>'Attendance changed on another device.','code'=>'conflict'],409);
        DB::transaction(function()use($data,$user,$term){
            foreach($data['records'] as $record) AttendanceRecord::updateOrCreate(
                ['student_id'=>$record['student_id'],'attendance_date'=>$data['attendance_date'],'session_key'=>$data['session_key']],
                ['school_id'=>$user->school_id,'term_id'=>$term->id,'school_class_id'=>$data['school_class_id'],'subject_id'=>$data['subject_id']??null,'status'=>$record['status'],'recorded_by'=>$user->id]
            );
        });
        AuditLog::record($user->school_id,'mobile.attendance.saved',null,['class_id'=>$data['school_class_id'],'count'=>count($data['records'])]);
        return $this->ok(['saved'=>count($data['records']),'version'=>now()->toISOString()]);
    }
}
