<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduation_records', function (Blueprint $table): void {
            $table->string('external_result_type')->nullable()->after('reversal_reason');
            $table->string('external_result')->nullable()->after('external_result_type');
            $table->timestamp('external_result_entered_at')->nullable()->after('external_result');
            $table->foreignId('external_result_entered_by')->nullable()->after('external_result_entered_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('graduation_records', function (Blueprint $table): void {
            $table->dropForeign(['external_result_entered_by']);
            $table->dropColumn(['external_result_type', 'external_result', 'external_result_entered_at', 'external_result_entered_by']);
        });
    }
};
