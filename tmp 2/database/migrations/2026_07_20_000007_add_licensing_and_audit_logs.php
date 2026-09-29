<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('license_plan')->default('standard')->after('license_expires_at');
            $table->string('license_status')->default('active')->after('license_plan');
            $table->timestamp('license_started_at')->nullable()->after('license_status');
            $table->unsignedInteger('license_student_limit')->nullable()->after('license_started_at');
        });
        DB::table('schools')->where('is_demo', true)->update(['license_status' => 'trial']);

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('audit_logs'); Schema::table('schools', function (Blueprint $table) {$table->dropColumn(['license_plan','license_status','license_started_at','license_student_limit']);}); }
};
