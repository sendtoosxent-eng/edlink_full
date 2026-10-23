<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_leaves', function (Blueprint $table): void {
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('staff_leave_handovers', function (Blueprint $table): void {
            $table->json('class_ids')->nullable();
            $table->boolean('temporary')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('staff_leave_handovers', fn (Blueprint $table) => $table->dropColumn(['class_ids', 'temporary']));
        Schema::table('staff_leaves', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn('returned_at');
        });
    }
};
