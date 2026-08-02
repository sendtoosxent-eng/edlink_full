<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->foreignId('school_group_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('branch_name')->nullable()->after('name');
        });

        Schema::create('school_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('admin');
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('can_view_group')->default(false);
            $table->timestamps();
            $table->unique(['school_id', 'user_id']);
        });

        DB::table('users')->whereNotNull('school_id')->orderBy('id')->each(function ($user): void {
            DB::table('school_user_access')->insertOrIgnore([
                'school_id' => $user->school_id, 'user_id' => $user->id,
                'role' => $user->role ?? 'admin', 'designation_id' => $user->designation_id,
                'can_view_group' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_user_access');
        Schema::table('schools', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_group_id');
            $table->dropColumn('branch_name');
        });
        Schema::dropIfExists('school_groups');
    }
};
