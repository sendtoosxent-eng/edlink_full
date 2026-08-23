<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_notification_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();
            $table->unique(['school_notification_id', 'user_id'], 'notification_user_read_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_notification_reads');
    }
};
