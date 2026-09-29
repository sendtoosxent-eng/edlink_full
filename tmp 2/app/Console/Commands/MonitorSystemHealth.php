<?php
namespace App\Console\Commands;
use App\Models\School;
use App\Models\SystemBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class MonitorSystemHealth extends Command {
 protected $signature='edlink:monitor'; protected $description='Check backups, queues, logs, and storage and notify school administrators.';
 public function handle():int{$issues=[];try{DB::select('select 1');}catch(\Throwable){$issues[]='Database connection failed.';}if(!is_writable(storage_path()))$issues[]='Storage is not writable.';if(DB::table('failed_jobs')->count()>0)$issues[]='One or more queue jobs have failed.';$last=SystemBackup::where('status','verified')->latest('verified_at')->first();if(!$last||$last->verified_at?->lt(now()->subDays(2)))$issues[]='No verified backup was created in the last 48 hours.';if(file_exists(storage_path('logs/laravel.log'))&&filesize(storage_path('logs/laravel.log'))>10*1024*1024)$issues[]='The application log exceeds 10 MB.';if($issues)foreach(School::pluck('id') as $schoolId)DB::table('school_notifications')->insert(['school_id'=>$schoolId,'title'=>'Edlink system health alert','message'=>implode(' ',$issues),'type'=>'warning','created_at'=>now(),'updated_at'=>now()]);$this->info($issues?implode(' ',$issues):'All monitored checks passed.');return $issues?self::FAILURE:self::SUCCESS;}
}
