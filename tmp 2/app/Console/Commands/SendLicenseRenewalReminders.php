<?php

namespace App\Console\Commands;

use App\Models\PlatformSetting;
use App\Models\School;
use App\Notifications\LicenseRenewalReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendLicenseRenewalReminders extends Command
{
    protected $signature='edlink:renewal-reminders';
    protected $description='Queue licence renewal reminders for school administrators.';

    public function handle(): int
    {
        $warningDays=(int)(PlatformSetting::where('key','renewal_warning_days')->value('value')?:30);
        $queued=0;
        School::whereNotNull('license_expires_at')->where('license_expires_at','<=',now()->addDays($warningDays))->where('license_expires_at','>=',today())->chunkById(100,function($schools)use(&$queued){foreach($schools as $school){$title='Licence renewal reminder';$alreadySent=DB::table('school_notifications')->where('school_id',$school->id)->where('title',$title)->whereDate('created_at',today())->exists();if($alreadySent)continue;$admins=$school->users()->whereIn('role',['admin','superadmin'])->whereNotNull('email')->get();if($admins->isEmpty())continue;$days=max(0,today()->diffInDays($school->license_expires_at,false));Notification::send($admins,new LicenseRenewalReminder($school->name,$school->school_number,$days));DB::table('school_notifications')->insert(['school_id'=>$school->id,'title'=>$title,'message'=>'Your Edlink licence expires '.$school->license_expires_at->diffForHumans().'.','type'=>'warning','created_at'=>now(),'updated_at'=>now()]);$queued+=$admins->count();}});
        $this->info('Queued '.$queued.' renewal reminder(s).');
        return self::SUCCESS;
    }
}
