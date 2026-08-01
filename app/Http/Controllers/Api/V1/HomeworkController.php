<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\HomeworkStoreRequest;
use App\Models\AuditLog;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkSubmission;
use App\Support\MobileAccess;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;

class HomeworkController extends ApiController
{
    public function index(Request $request)
    {
        $user=$request->user();
        if(TeacherAcademicScope::isTeacher($user)) $query=HomeworkAssignment::where('school_id',$user->school_id)->where('teacher_id',$user->id);
        else { $student=MobileAccess::student($user,$request->integer('student_id')?:null); $query=HomeworkAssignment::where('school_id',$user->school_id)->whereNotNull('published_at')->where('school_class_id',$student->school_class_id)->where(fn($q)=>$q->whereNull('stream_id')->orWhere('stream_id',$student->stream_id)); }
        return $this->ok($query->with(['subject:id,name','schoolClass:id,name'])->orderBy('due_at')->paginate(20));
    }
    public function store(HomeworkStoreRequest $request)
    {
        $user=$request->user(); abort_unless(TeacherAcademicScope::isTeacher($user),403); $data=$request->validated();
        MobileAccess::teacherStudentQuery($user,$data['school_class_id'],$data['subject_id'])->exists();
        $assignment=HomeworkAssignment::create([...$data,'school_id'=>$user->school_id,'term_id'=>$user->school->currentTerm()->id,'teacher_id'=>$user->id,'published_at'=>now()]);
        AuditLog::record($user->school_id,'mobile.homework.created',$assignment);
        return $this->ok($assignment);
    }
    public function show(Request $request,int $assignment)
    {
        $item=MobileAccess::homework($request->user(),$assignment,$request->integer('student_id')?:null);
        $item->load(['subject:id,name','schoolClass:id,name']);
        if (TeacherAcademicScope::isTeacher($request->user())) {
            $item->load(['submissions.student:id,name,admission_no']);
        } else {
            $student=MobileAccess::student($request->user(),$request->integer('student_id')?:null);
            $item->setRelation('submissions',$item->submissions()->where('student_id',$student->id)->get());
        }
        return $this->ok($item);
    }
    public function submit(Request $request,int $assignment)
    {
        $data=$request->validate(['student_id'=>['nullable','integer'],'answer'=>['required','string','max:20000'],'base_version'=>['nullable','date']]);
        $item=MobileAccess::homework($request->user(),$assignment,$data['student_id']??null);
        $student=MobileAccess::student($request->user(),$data['student_id']??null);
        $existing=HomeworkSubmission::where(['homework_assignment_id'=>$item->id,'student_id'=>$student->id])->first();
        if($existing&&!empty($data['base_version'])&&$existing->updated_at->gt($data['base_version'])) return response()->json(['message'=>'Submission changed on another device.','code'=>'conflict'],409);
        $submission=HomeworkSubmission::updateOrCreate(['homework_assignment_id'=>$item->id,'student_id'=>$student->id],['submitted_by'=>$request->user()->id,'answer'=>$data['answer'],'submitted_at'=>now(),'status'=>'submitted']);
        return $this->ok($submission);
    }

    public function review(Request $request,int $assignment,int $submission)
    {
        $data=$request->validate(['score'=>['nullable','numeric','min:0'],'feedback'=>['nullable','string','max:10000']]);
        $item=MobileAccess::homework($request->user(),$assignment);
        abort_unless(TeacherAcademicScope::isTeacher($request->user()),403);
        if($data['score']!==null) abort_if($data['score']>$item->maximum_score,422,'The score exceeds the assignment maximum.');
        $record=HomeworkSubmission::where('homework_assignment_id',$item->id)->whereKey($submission)->firstOrFail();
        $record->update(['score'=>$data['score'],'feedback'=>$data['feedback']??null,'reviewed_at'=>now(),'status'=>'reviewed']);
        AuditLog::record($request->user()->school_id,'mobile.homework.reviewed',$record,['assignment_id'=>$item->id]);
        return $this->ok($record);
    }
}
