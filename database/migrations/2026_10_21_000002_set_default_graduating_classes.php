<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('schools')->orderBy('id')->get(['id', 'school_type'])->each(function ($school): void {
            if (DB::table('school_classes')->where('school_id', $school->id)->where('is_graduating_class', true)->exists()) {
                return;
            }
            $types = match ($school->school_type) {
                'primary' => ['primary'],
                'secondary' => ['secondary'],
                'combined' => ['primary', 'secondary'],
                default => [],
            };
            if ($types === []) return;
            DB::table('school_classes')->where('school_id', $school->id)->get()->each(function ($class) use ($types): void {
                $name = strtolower(preg_replace('/[^a-z0-9]+/', ' ', $class->name));
                $primarySeven = in_array('primary', $types, true) && (str_contains($name, 'primary seven') || preg_match('/\bp7\b|\bseven\b/', $name));
                $s4 = in_array('secondary', $types, true) && preg_match('/\bs ?4\b|\bsenior four\b/', $name);
                $s6 = in_array('secondary', $types, true) && preg_match('/\bs ?6\b|\bsenior six\b/', $name);
                if ($primarySeven || $s4 || $s6) DB::table('school_classes')->where('id', $class->id)->update(['is_graduating_class' => true]);
            });
        });
    }

    public function down(): void {}
};
