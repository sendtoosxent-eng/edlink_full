<?php
namespace App\Services;
use App\Models\GradingScale;use App\Models\School;use App\Models\SchoolClass;use App\Models\SchoolSetting;
class SchoolAcademicSetup
{
 public static function stagesFor(School $school):array{return match($school->school_type){'primary'=>['primary'],'secondary'=>['lower_secondary','advanced_level'],'tertiary'=>['tertiary'],default=>['kindergarten']};}
 public static function provision(School $school):void
 {
  $classes=match($school->school_type){
   'primary'=>collect(range(1,7))->map(fn($n)=>["Primary {$n}",'primary',$n]),
   'secondary'=>collect(range(1,6))->map(fn($n)=>["Senior {$n}",$n<=4?'lower_secondary':'advanced_level',$n]),
   'kindergarten'=>collect([['Baby Class','kindergarten',1],['Middle Class','kindergarten',2],['Top Class','kindergarten',3]]),
   'tertiary'=>collect([['Certificate Year 1','tertiary',1],['Certificate Year 2','tertiary',2],['Diploma Year 1','tertiary',3],['Diploma Year 2','tertiary',4]]),
   default=>collect(),
  };
  foreach($classes as[$name,$stage,$order])SchoolClass::updateOrCreate(['school_id'=>$school->id,'name'=>$name],['education_stage'=>$stage,'is_system'=>true,'sort_order'=>$order]);
  foreach(self::stagesFor($school)as$stage){self::installDefaultScale($school,$stage);foreach(self::defaultReportSettings($stage)as$key=>$value)SchoolSetting::firstOrCreate(['school_id'=>$school->id,'key'=>"report_{$stage}_{$key}"],['value'=>(string)$value]);}
 }
 public static function installDefaultScale(School $school,string $stage):void
 {
  if(GradingScale::where('school_id',$school->id)->where('education_stage',$stage)->exists())return;
  $bands=match($stage){
   'primary','lower_secondary'=>[['D1',90,100,1,'Excellent'],['D2',80,89.99,2,'Very good'],['C3',70,79.99,3,'Good'],['C4',65,69.99,4,'Good'],['C5',60,64.99,5,'Credit'],['C6',55,59.99,6,'Credit'],['P7',50,54.99,7,'Pass'],['P8',40,49.99,8,'Pass'],['F9',0,39.99,9,'Fail']],
   'advanced_level'=>[['A',80,100,1,'Excellent'],['B',70,79.99,2,'Very good'],['C',60,69.99,3,'Good'],['D',55,59.99,4,'Satisfactory'],['E',50,54.99,5,'Pass'],['O',40,49.99,6,'Subsidiary pass'],['F',0,39.99,7,'Fail']],
   default=>[['A',80,100,null,'Excellent'],['B',70,79.99,null,'Very good'],['C',60,69.99,null,'Good'],['D',50,59.99,null,'Developing'],['F',0,49.99,null,'Needs support']]
  };
  foreach($bands as[$grade,$min,$max,$points,$remark])GradingScale::create(['school_id'=>$school->id,'education_stage'=>$stage,'grade'=>$grade,'minimum_percentage'=>$min,'maximum_percentage'=>$max,'aggregate_points'=>$points,'remark'=>$remark]);
 }
 public static function defaultReportSettings(string $stage):array{return['pass_mark'=>50,'best_subjects'=>$stage==='lower_secondary'?8:20,'show_position'=>'enabled','show_fees'=>'enabled','show_attendance'=>'enabled','show_promotion'=>'disabled','next_term_starts'=>'','footer'=>''];}
}
