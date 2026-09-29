<?php
namespace App\Services;
use App\Models\AttendanceRecord;use App\Models\GradingScale;use App\Models\Student;use App\Models\StudentEnrolment;use App\Models\Term;use Illuminate\Support\Facades\DB;
class StageTermReportCalculator
{
 public static function student(Student $student,Term $term):array
 {
  $enrolment=StudentEnrolment::with('schoolClass')->where(['school_id'=>$student->school_id,'student_id'=>$student->id,'term_id'=>$term->id])->first();
  $class=$enrolment?->schoolClass??$student->schoolClass;$stage=$class?->education_stage??'kindergarten';$settings=StageReportSettings::get($student->school_id,$stage);$scales=GradingScale::where('school_id',$student->school_id)->where('education_stage',$stage)->orderByDesc('minimum_percentage')->get();
  $marks=DB::table('exam_marks')->join('exam_papers','exam_papers.id','=','exam_marks.exam_paper_id')->join('exams','exams.id','=','exam_papers.exam_id')->join('exam_paper_submissions','exam_paper_submissions.exam_paper_id','=','exam_papers.id')->join('subjects','subjects.id','=','exam_papers.subject_id')->where(['exam_marks.student_id'=>$student->id,'exams.term_id'=>$term->id,'exam_paper_submissions.status'=>'approved'])->select('subjects.name',DB::raw('sum(exam_marks.score) as score'),DB::raw('sum(exam_papers.maximum_score) as maximum'))->groupBy('subjects.id','subjects.name')->get()->map(function($row)use($scales){$percentage=$row->maximum?round($row->score/$row->maximum*100,2):0;$scale=$scales->first(fn($s)=>$percentage>=$s->minimum_percentage&&$percentage<=$s->maximum_percentage);return['subject'=>$row->name,'score'=>$row->score,'maximum'=>$row->maximum,'percentage'=>$percentage,'grade'=>$scale?->grade??'-','points'=>$scale?->aggregate_points,'comment'=>$scale?->remark??''];});
  $used=$marks->sortByDesc('percentage')->take($settings['best']);$average=$used->count()?round($used->avg('percentage'),2):0;
  $attendance=AttendanceRecord::where(['school_id'=>$student->school_id,'term_id'=>$term->id,'student_id'=>$student->id])->where('session_key','daily')->get();
  if($attendance->isEmpty())$attendance=AttendanceRecord::where(['school_id'=>$student->school_id,'term_id'=>$term->id,'student_id'=>$student->id])->get();
  $overall=$scales->first(fn($scale)=>$average>=(float)$scale->minimum_percentage&&$average<=(float)$scale->maximum_percentage);return compact('settings','marks','average','class','scales')+['stage'=>$stage,'scale_configured'=>$scales->isNotEmpty(),'passed'=>$marks->isNotEmpty()&&$average>=$settings['pass'],'aggregate'=>$used->sum(fn($mark)=>(int)($mark['points']??0)),'attendance_present'=>$attendance->whereIn('status',['present','late'])->count(),'attendance_total'=>$attendance->count(),'fees'=>['due'=>$student->totalDue($term),'paid'=>$student->totalPaid($term),'balance'=>$student->balance($term)],'promotion'=>$enrolment?->promotion_outcome,'teacher_remarks'=>$marks->isEmpty()?'No approved results are available.':trim(($overall?->remark??'Keep working consistently.').' Overall result: '.($average>=$settings['pass']?'Pass.':'Below the configured pass mark.'))];
 }
}
