<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduation_records', function (Blueprint $table): void {
            $table->text('external_result')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Retain the wider column so a rollback cannot truncate saved results.
    }
};
