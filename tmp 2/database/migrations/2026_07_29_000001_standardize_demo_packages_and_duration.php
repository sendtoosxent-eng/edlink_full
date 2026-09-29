<?php

use App\Support\SubscriptionPlans;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('license_plan')->default('basic')->change();
        });

        DB::table('schools')
            ->whereNotIn('license_plan', array_keys(SubscriptionPlans::PLANS))
            ->update([
                'license_plan' => 'basic',
                'license_student_limit' => SubscriptionPlans::limit('basic'),
                'updated_at' => now(),
            ]);

        DB::table('landing_page_settings')
            ->where('key', 'announcement')
            ->where('value', 'Free 7-day demo — no card required')
            ->update(['value' => 'Free 10-day demo — no card required', 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('license_plan')->default('standard')->change();
        });
    }
};
