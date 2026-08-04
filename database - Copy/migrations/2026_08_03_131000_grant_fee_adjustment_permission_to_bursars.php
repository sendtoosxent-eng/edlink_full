<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('designations')->whereRaw('lower(name) = ?', ['bursar'])->orderBy('id')->each(function ($designation): void {
            $permissions = json_decode($designation->permissions ?: '[]', true) ?: [];
            if (! in_array('finance.adjustments', $permissions, true)) $permissions[] = 'finance.adjustments';
            DB::table('designations')->where('id', $designation->id)->update(['permissions' => json_encode(array_values($permissions)), 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        DB::table('designations')->whereRaw('lower(name) = ?', ['bursar'])->orderBy('id')->each(function ($designation): void {
            $permissions = array_values(array_filter(json_decode($designation->permissions ?: '[]', true) ?: [], fn ($permission) => $permission !== 'finance.adjustments'));
            DB::table('designations')->where('id', $designation->id)->update(['permissions' => json_encode($permissions), 'updated_at' => now()]);
        });
    }
};
