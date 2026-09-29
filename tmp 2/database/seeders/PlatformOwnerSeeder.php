<?php

namespace Database\Seeders;

use App\Models\PlatformAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformOwnerSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local','testing'])) {
            $this->command?->warn('Platform owner seed skipped outside local/testing.'); return;
        }
        PlatformAdmin::updateOrCreate(['email'=>'platform@edlink.test'], ['name'=>'Edlink Platform Owner','password'=>Hash::make('Edlink@2026!'),'role'=>'platform_owner','is_active'=>true]);
        $this->command?->info('Local platform owner is ready at platform@edlink.test.');
    }
}