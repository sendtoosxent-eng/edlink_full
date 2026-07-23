<?php

use App\Models\Designation;
use App\Models\School;
use App\Support\DesignationPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        School::query()->each(function (School $school) {
            $ids = [];
            foreach (DesignationPermissions::defaults() as $name => $permissions) {
                $designation = Designation::firstOrCreate(
                    ['school_id' => $school->id, 'name' => $name],
                    ['description' => 'Default Edlink access designation.', 'permissions' => $permissions]
                );
                $ids[$name] = $designation->id;
            }

            DB::table('users')->where('school_id', $school->id)->whereNull('designation_id')->where('role', 'teacher')->update(['designation_id' => $ids['Subject Teacher']]);
            DB::table('users')->where('school_id', $school->id)->whereNull('designation_id')->where('role', 'bursar')->update(['designation_id' => $ids['Bursar']]);
            DB::table('users')->where('school_id', $school->id)->whereNull('designation_id')->where('role', 'academic_admin')->update(['designation_id' => $ids['DOS']]);
        });
    }

    public function down(): void
    {
        // Access assignments are intentionally preserved to avoid locking staff out.
    }
};