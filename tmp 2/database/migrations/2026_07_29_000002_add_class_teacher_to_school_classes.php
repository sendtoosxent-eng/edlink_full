<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('class_teacher_user_id')->nullable()->after('school_id')->constrained('users')->nullOnDelete();
        });
        DB::table('designations')->whereIn('name', ['Class Teacher','Subject Teacher'])->orderBy('id')->each(function ($designation): void {
            $permissions = collect(json_decode($designation->permissions ?: '[]', true))->reject(fn($permission)=>$permission==='students.view');
            if ($designation->name === 'Class Teacher') $permissions->push('exams.marks');
            DB::table('designations')->where('id',$designation->id)->update(['permissions'=>$permissions->unique()->values()->toJson(),'updated_at'=>now()]);
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', fn (Blueprint $table) => $table->dropConstrainedForeignId('class_teacher_user_id'));
    }
};
