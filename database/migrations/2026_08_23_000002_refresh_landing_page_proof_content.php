<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            'stat_one_value' => ['4', '15+'],
            'stat_one_label' => ['Core modules', 'Connected modules'],
            'stat_two_value' => ['2 min', '6'],
            'stat_two_label' => ['Setup time', 'Role-based portals'],
            'stat_three_value' => ['7 days', '10 days'],
            'about_image' => ['img/testimonials.png', 'img/teacher-explaining.png'],
        ];

        foreach ($replacements as $key => [$old, $new]) {
            DB::table('landing_page_settings')->where('key', $key)->where('value', $old)->update(['value' => $new, 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};
