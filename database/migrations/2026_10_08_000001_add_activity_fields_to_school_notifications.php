<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_notifications', function (Blueprint $table): void {
            $table->foreignId('student_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('category', 50)->default('general')->after('type');
            $table->string('event_key')->nullable()->after('category');
            $table->index(['school_id', 'user_id', 'category'], 'school_notification_recipient_category_index');
            $table->unique(['school_id', 'user_id', 'event_key'], 'school_notification_event_unique');
        });
    }

    public function down(): void
    {
        Schema::table('school_notifications', function (Blueprint $table): void {
            $table->dropUnique('school_notification_event_unique');
            $table->dropIndex('school_notification_recipient_category_index');
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn(['category', 'event_key']);
        });
    }
};
