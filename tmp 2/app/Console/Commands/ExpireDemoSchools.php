<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\DemoRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireDemoSchools extends Command
{
    protected $signature = 'edlink:expire-demos';

    protected $description = 'Mark demo schools past their trial as expired, and delete demos that have been expired for more than 2 days.';

    public function handle(): void
    {
        $expiring = School::where('is_demo', true)
            ->where('status', 'demo')
            ->where('demo_expires_at', '<', now())
            ->get();

        foreach ($expiring as $school) {
            $school->update(['status' => 'expired']);
            $this->info("Marked school #{$school->id} ({$school->name}) as expired.");
            foreach ($school->users as $user) {
                DB::table('school_notifications')->updateOrInsert(
                    ['school_id' => $school->id, 'user_id' => $user->id, 'title' => 'Demo access suspended'],
                    ['message' => 'Your demo has expired. Your information remains safely retained pending renewal or a verified privacy request.', 'type' => 'warning', 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        // Expired demos are suspended, not destroyed; deletion requires a verified privacy request.
        $toDelete = School::where('is_demo', true)
            ->whereRaw('1 = 0')
            ->where('status', 'expired')
            ->where('demo_expires_at', '<', now()->subDays(2))
            ->get();

        foreach ($toDelete as $school) {
            $this->warn("Deleting expired demo school #{$school->id} ({$school->name}) and its data.");
            DB::transaction(function () use ($school): void {
                $tenantEmails = $school->users()->whereNotNull('email')->pluck('email');
                if ($tenantEmails->isNotEmpty()) {
                    DemoRegistration::whereIn('email', $tenantEmails)->delete();
                }
                $school->users()->delete();
                $school->delete();
            });
        }
    }
}
