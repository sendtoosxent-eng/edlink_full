<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Support\DemoAccounts;
use Database\Seeders\TeacherSubjectVisibilitySeeder;
use Illuminate\Console\Command;

class EnsureDemoAccounts extends Command
{
    protected $signature = 'edlink:ensure-demo-accounts';

    protected $description = 'Provision the public role-based demonstration accounts when they are missing';

    public function handle(): int
    {
        $school = School::where('school_number', DemoAccounts::schoolNumber())->first();
        $emails = collect(DemoAccounts::roles())->pluck('email');
        $complete = $school
            && $school->users()->whereIn('email', $emails)->count() === $emails->count()
            && SchoolSetting::getValue($school->id, 'public_demo_seed_version') === DemoAccounts::SEED_VERSION;

        if ($complete) {
            $this->info('Public demo accounts are ready.');

            return self::SUCCESS;
        }

        $this->call('db:seed', [
            '--class' => TeacherSubjectVisibilitySeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
