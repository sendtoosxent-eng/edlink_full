<?php
namespace App\Services;
use App\Models\SchoolSetting;
class StageReportSettings
{
 public static function get(int $schoolId,string $stage):array
 {
  $values=SchoolSetting::where('school_id',$schoolId)->pluck('value','key');$prefix="report_{$stage}_";$defaults=SchoolAcademicSetup::defaultReportSettings($stage);
  return['stage'=>$stage,'level'=>$stage,'pass'=>(float)($values[$prefix.'pass_mark']??$defaults['pass_mark']),'best'=>(int)($values[$prefix.'best_subjects']??$defaults['best_subjects']),'calculation_method'=>$values[$prefix.'calculation_method']??$defaults['calculation_method'],'missing_assessment_rule'=>$values[$prefix.'missing_assessment_rule']??$defaults['missing_assessment_rule'],'assessment_weights'=>json_decode($values[$prefix.'assessment_weights']??$defaults['assessment_weights'],true)?:[],'show_position'=>($values[$prefix.'show_position']??$defaults['show_position'])==='enabled','show_fees'=>($values[$prefix.'show_fees']??$defaults['show_fees'])==='enabled','show_attendance'=>($values[$prefix.'show_attendance']??$defaults['show_attendance'])==='enabled','show_promotion'=>($values[$prefix.'show_promotion']??$defaults['show_promotion'])==='enabled','show_marks'=>($values[$prefix.'show_marks']??$defaults['show_marks'])==='enabled','show_maximum'=>($values[$prefix.'show_maximum']??$defaults['show_maximum'])==='enabled','show_percentage'=>($values[$prefix.'show_percentage']??$defaults['show_percentage'])==='enabled','show_grade'=>($values[$prefix.'show_grade']??$defaults['show_grade'])==='enabled','show_points'=>($values[$prefix.'show_points']??$defaults['show_points'])==='enabled','show_remarks'=>($values[$prefix.'show_remarks']??$defaults['show_remarks'])==='enabled','next_term_starts'=>$values[$prefix.'next_term_starts']??$defaults['next_term_starts'],'footer'=>$values[$prefix.'footer']??$defaults['footer']];
 }
}
