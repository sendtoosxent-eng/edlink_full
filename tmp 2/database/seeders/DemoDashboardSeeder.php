<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\CashPoolEntry;
use App\Models\Exam;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        School::where('is_demo', true)->each(function (School $school) {
            $term = $school->currentTerm(); $students = $school->students()->where('status', 'active')->get();
            if (! $term || $students->isEmpty()) return;
            foreach ($students as $index => $student) if (blank($student->gender)) $student->update(['gender' => $index % 2 ? 'female' : 'male']);
            foreach (range(0, 4) as $offset) foreach ($students as $index => $student) { $date=now()->subDays($offset)->toDateString(); if (! AttendanceRecord::where('student_id',$student->id)->whereDate('attendance_date',$date)->exists()) AttendanceRecord::create(['school_id'=>$school->id,'term_id'=>$term->id,'student_id'=>$student->id,'attendance_date'=>$date,'status'=>$index % 9 === 0 ? 'absent' : ($index % 7 === 0 ? 'late' : 'present'),'recorded_by'=>null]); }
            foreach ($students->take(5) as $index => $student) if (! FeePayment::where('student_id',$student->id)->where('term_id',$term->id)->exists()) { $payment=FeePayment::create(['school_id'=>$school->id,'student_id'=>$student->id,'term_id'=>$term->id,'amount'=>50000+($index*25000),'method'=>'cash','paid_at'=>now()->subDays($index+1)]); CashPoolEntry::create(['school_id'=>$school->id,'term_id'=>$term->id,'fee_payment_id'=>$payment->id,'direction'=>'credit','amount'=>$payment->amount,'description'=>'Demo fee payment','transacted_at'=>$payment->paid_at]); }
            foreach ([['Staff meeting',now()->addDays(2),'staff'],['Parents day',now()->addDays(7),'parent'],['Mid-term assessment',now()->addDays(14),'academic']] as [$title,$date,$type]) DB::table('school_events')->updateOrInsert(['school_id'=>$school->id,'title'=>$title,'event_date'=>$date->toDateString()],['term_id'=>$term->id,'type'=>$type,'updated_at'=>now(),'created_at'=>now()]);
            $class=$school->classes()->first(); if($class) foreach ([['08:00:00','08:40:00','Mathematics'],['08:40:00','09:20:00','English'],['09:40:00','10:20:00','Science']] as [$start,$end,$label]) foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day) DB::table('timetable_slots')->updateOrInsert(['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$class->id,'day_of_week'=>$day,'starts_at'=>$start],['ends_at'=>$end,'label'=>$label,'updated_at'=>now(),'created_at'=>now()]);
            if ($class && ! DB::table('exam_paper_submissions')->join('exam_papers','exam_papers.id','=','exam_paper_submissions.exam_paper_id')->join('exams','exams.id','=','exam_papers.exam_id')->where('exams.school_id',$school->id)->where('exams.term_id',$term->id)->where('exam_paper_submissions.status','approved')->exists()) { $exam=Exam::firstOrCreate(['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$class->id,'name'=>'Demo Mid-Term Assessment'],['status'=>'published','published_at'=>now()]); foreach(['Mathematics','English','Science'] as $number=>$name){$subject=Subject::firstOrCreate(['school_id'=>$school->id,'name'=>$name],['code'=>strtoupper(substr($name,0,3))]);DB::table('exam_papers')->updateOrInsert(['exam_id'=>$exam->id,'subject_id'=>$subject->id],['maximum_score'=>100,'weighting'=>1,'updated_at'=>now(),'created_at'=>now()]);$paperId=DB::table('exam_papers')->where(['exam_id'=>$exam->id,'subject_id'=>$subject->id])->value('id');foreach($students as $index=>$student)DB::table('exam_marks')->updateOrInsert(['exam_paper_id'=>$paperId,'student_id'=>$student->id],['score'=>60+(($index*7+$number*9)%35),'updated_at'=>now(),'created_at'=>now()]);DB::table('exam_paper_submissions')->updateOrInsert(['exam_paper_id'=>$paperId],['status'=>'approved','approved_at'=>now(),'updated_at'=>now(),'created_at'=>now()]);} }
            DB::table('school_notifications')->updateOrInsert(['school_id'=>$school->id,'title'=>'Demo dashboard data ready'],['message'=>'Attendance, payments, events and notifications are sample records for this demo school.','type'=>'info','updated_at'=>now(),'created_at'=>now()]);
        });
    }
}
