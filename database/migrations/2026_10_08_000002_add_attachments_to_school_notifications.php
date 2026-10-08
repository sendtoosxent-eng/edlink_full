<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_notifications', function (Blueprint $table): void {
            $table->string('attachment_name')->nullable()->after('event_key');
            $table->string('attachment_path')->nullable()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('school_notifications', function (Blueprint $table): void {
            $table->dropColumn(['attachment_name', 'attachment_path']);
        });
    }
};
