<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_number')->nullable()->unique()->after('school_id');
            $table->string('phone')->nullable()->after('email');
            $table->string('job_title')->nullable()->after('role');
            $table->decimal('base_salary', 12, 2)->default(0)->after('job_title');
            $table->string('employment_status')->default('active')->after('base_salary');
            $table->date('joined_at')->nullable()->after('employment_status');
        });
        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); $table->date('starts_on'); $table->date('ends_on'); $table->string('status')->default('pending'); $table->text('reason')->nullable(); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete(); $table->string('period'); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->decimal('amount', 12, 2); $table->timestamp('paid_at'); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['user_id', 'period']);
        });
        Schema::create('subjects', function (Blueprint $table) { $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->string('name'); $table->string('code')->nullable(); $table->timestamps(); $table->unique(['school_id', 'name']); });
        Schema::create('staff_subjects', function (Blueprint $table) { $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('subject_id')->constrained()->cascadeOnDelete(); $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete(); $table->timestamps(); $table->unique(['user_id', 'subject_id', 'school_class_id']); });
    }
    public function down(): void { Schema::dropIfExists('staff_subjects'); Schema::dropIfExists('subjects'); Schema::dropIfExists('payroll_runs'); Schema::dropIfExists('staff_leaves'); Schema::table('users', function (Blueprint $table) { $table->dropColumn(['staff_number','phone','job_title','base_salary','employment_status','joined_at']); }); }
};
