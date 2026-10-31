<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->uuid('client_token');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['sender_id', 'client_token']);
            $table->index(['school_id', 'recipient_id', 'read_at']);
            $table->index(['school_id', 'sender_id', 'recipient_id', 'id'], 'chat_thread_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
