<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;

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
            // TODO: notify the school admin their demo has expired.
        }

        $toDelete = School::where('is_demo', true)
            ->where('status', 'expired')
            ->where('demo_expires_at', '<', now()->subDays(2))
            ->get();

        foreach ($toDelete as $school) {
            $this->warn("Deleting expired demo school #{$school->id} ({$school->name}) and its data.");
            $school->students()->delete();
            $school->classes()->delete();
            $school->users()->delete();
            $school->delete();
        }
    }
}
