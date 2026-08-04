<?php
namespace Database\Seeders;
use App\Models\School;use App\Services\SchoolAcademicSetup;use Illuminate\Database\Seeder;
class SchoolTypeDemoSeeder extends Seeder
{
 public function run():void
 {
  School::where('is_demo',true)->orderBy('id')->get()->each(function(School $school,int $index){$school->update(['school_type'=>$index%2===0?'primary':'secondary']);SchoolAcademicSetup::provision($school);});
 }
}
