<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Support\DemoAccounts;
use Database\Seeders\PublicDemoSchoolsSeeder;
use Illuminate\Console\Command;

class EnsureDemoAccounts extends Command
{
    protected $signature = 'edlink:ensure-demo-accounts';

    protected $description = 'Provision the public role-based demonstration accounts when they are missing';

    public function handle(): int
    {
        $emails = collect(DemoAccounts::roles())->pluck('email');
        $schools = School::whereIn('school_number', collect(DemoAccounts::schoolTypes())->pluck('school_number'))->get();
        $complete = $schools->count() === count(DemoAccounts::schoolTypes())
            && $schools->every(fn (School $school) =>
                $school->users()->whereIn('email', $emails)->count() === $emails->count()
                && SchoolSetting::getValue($school->id, 'public_demo_seed_version') === DemoAccounts::SEED_VERSION
            )
            && School::where('is_demo', true)->whereNotNull('school_group_id')->count() >= 8;

        if ($complete) {
            $this->info('Public demo accounts are ready.');

            return self::SUCCESS;
        }

        $this->call('db:seed', [
            '--class' => PublicDemoSchoolsSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
