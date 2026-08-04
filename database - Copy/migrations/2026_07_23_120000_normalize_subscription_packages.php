<?php

use App\Support\SubscriptionPlans;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('schools')->orderBy('id')->each(function ($school) {
            $activeStudents = DB::table('students')->where('school_id', $school->id)->where('status', 'active')->count();
            $plan = SubscriptionPlans::suggestedFor($activeStudents);
            DB::table('schools')->where('id', $school->id)->update([
                'license_plan' => $plan,
                'license_student_limit' => SubscriptionPlans::limit($plan),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void {}
};