<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\MarksUpdateRequest;
use App\Models\AuditLog;
use App\Models\ExamPaper;
use App\Support\MobileAccess;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarksController extends ApiController
{
    private function paper(Request $request, int $id): ExamPaper
    {
        $paper=ExamPaper::with(['exam','subject'])->findOrFail($id);
        abort_unless($paper->exam->school_id===$request->user()->school_id,404);
        abort_unless(TeacherAcademicScope::canEnterPaper($request->user(),$paper->exam->school_class_id,$paper->subject_id,$paper->exam->term_id),403);
        return $paper;
    }
    public function index(Request $request)
    {
        abort_unless(TeacherAcademicScope::isTeacher($request->user()),403);
        $papers=ExamPaper::with(['exam.schoolClass','subject'])->whereHas('exam',fn($q)=>$q->where('school_id',$request->user()->school_id))->get()
            ->filter(fn($p)=>TeacherAcademicScope::canEnterPaper($request->user(),$p->exam->school_class_id,$p->subject_id,$p->exam->term_id))->values();
        return $this->ok($papers);
    }
    public function show(Request $request,int $paper)
    {
        $paper=$this->paper($request,$paper);
        $students=MobileAccess::teacherStudentQuery($request->user(),$paper->exam->school_class_id,$paper->subject_id)->orderBy('name')->get(['id','name','admission_no']);
        $marks=DB::table('exam_marks')->where('exam_paper_id',$paper->id)->get()->keyBy('student_id');
        $submission=DB::table('exam_paper_submissions')->where('exam_paper_id',$paper->id)->first();
        return $this->ok(['paper'=>$paper,'status'=>$submission?->status??'draft','students'=>$students->map(fn($s)=>['student'=>$s,'score'=>$marks->get($s->id)?->score])],
            ['version'=>$marks->max('updated_at')]);
    }
    public function update(MarksUpdateRequest $request,int $paper)
    {
        $paper=$this->paper($request,$paper); $data=$request->validated();
        $submission=DB::table('exam_paper_submissions')->where('exam_paper_id',$paper->id)->first();
        abort_if($submission && $submission->status!=='draft',409,'Submitted marks are read-only.');
        $allowed=MobileAccess::teacherStudentQuery($request->user(),$paper->exam->school_class_id,$paper->subject_id)->pluck('id');
        abort_unless(collect($data['marks'])->pluck('student_id')->diff($allowed)->isEmpty(),403);
        foreach($data['marks'] as $mark) abort_if($mark['score']!==null && $mark['score']>$paper->maximum_score,422,'A score exceeds the paper maximum.');
        $version=DB::table('exam_marks')->where('exam_paper_id',$paper->id)->max('updated_at');
        if(!empty($data['base_version'])&&$version&&$version>$data['base_version']) return response()->json(['message'=>'Marks changed on another device.','code'=>'conflict'],409);
        DB::transaction(function()use($data,$paper,$request){foreach($data['marks'] as $mark)DB::table('exam_marks')->updateOrInsert(['exam_paper_id'=>$paper->id,'student_id'=>$mark['student_id']],['score'=>$mark['score'],'entered_by'=>$request->user()->id,'created_at'=>now(),'updated_at'=>now()]);});
        return $this->ok(['saved'=>count($data['marks']),'version'=>now()->toISOString()]);
    }
    public function submit(Request $request,int $paper)
    {
        $paper=$this->paper($request,$paper);
        DB::table('exam_paper_submissions')->updateOrInsert(['exam_paper_id'=>$paper->id],['status'=>'submitted','submitted_by'=>$request->user()->id,'submitted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        AuditLog::record($request->user()->school_id,'mobile.marks.submitted',$paper);
        return $this->ok(['status'=>'submitted']);
    }
}
