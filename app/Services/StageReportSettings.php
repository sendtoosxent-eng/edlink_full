<?php
namespace App\Services;
use App\Models\SchoolSetting;
class StageReportSettings
{
 public static function get(int $schoolId,string $stage):array
 {
  $values=SchoolSetting::where('school_id',$schoolId)->pluck('value','key');$prefix="report_{$stage}_";$defaults=SchoolAcademicSetup::defaultReportSettings($stage);
  return['stage'=>$stage,'level'=>$stage,'pass'=>(float)($values[$prefix.'pass_mark']??$defaults['pass_mark']),'best'=>(int)($values[$prefix.'best_subjects']??$defaults['best_subjects']),'show_position'=>($values[$prefix.'show_position']??$defaults['show_position'])==='enabled','show_fees'=>($values[$prefix.'show_fees']??$defaults['show_fees'])==='enabled','show_attendance'=>($values[$prefix.'show_attendance']??$defaults['show_attendance'])==='enabled','show_promotion'=>($values[$prefix.'show_promotion']??$defaults['show_promotion'])==='enabled','next_term_starts'=>$values[$prefix.'next_term_starts']??$defaults['next_term_starts'],'footer'=>$values[$prefix.'footer']??$defaults['footer']];
 }
}
