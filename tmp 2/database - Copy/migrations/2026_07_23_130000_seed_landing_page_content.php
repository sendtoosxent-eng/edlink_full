<?php

use App\Models\LandingPageSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        foreach (LandingPageSetting::DEFAULTS as $key => $value) {
            LandingPageSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void {}
};