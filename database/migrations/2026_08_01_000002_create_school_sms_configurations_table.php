<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_sms_configurations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('provider', 40)->default('africastalking');
            $table->text('api_key')->nullable();
            $table->string('api_username', 150)->nullable();
            $table->string('sender_id', 20)->nullable();
            $table->string('endpoint', 500)->nullable();
            $table->text('webhook_secret')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_sms_configurations');
    }
};
