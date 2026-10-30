<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_whatsapp_configurations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $t->uuid('webhook_key')->unique();
            $t->string('business_account_id')->nullable();
            $t->string('phone_number_id')->nullable()->unique();
            $t->string('display_phone')->nullable();
            $t->text('access_token')->nullable();
            $t->text('app_secret')->nullable();
            $t->text('verify_token');
            $t->boolean('enabled')->default(false);
            $t->timestamp('connected_at')->nullable();
            $t->json('templates')->nullable();
            $t->timestamps();
        });
        Schema::create('whatsapp_contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('phone', 20);
            $t->timestamp('consented_at')->nullable();
            $t->foreignId('consented_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('consent_source', 500)->nullable();
            $t->timestamp('opted_out_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'phone']);
        });
        Schema::create('whatsapp_broadcasts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title');
            $t->string('preview_key', 64)->unique();
            $t->string('phone_number_id');
            $t->string('template_name');
            $t->string('language');
            $t->text('body');
            $t->text('footer')->nullable();
            $t->json('parameters');
            $t->string('audience');
            $t->unsignedInteger('recipient_count');
            $t->timestamps();
        });
        Schema::create('whatsapp_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('whatsapp_broadcast_id')->constrained()->cascadeOnDelete();
            $t->foreignId('whatsapp_contact_id')->constrained()->cascadeOnDelete();
            $t->string('phone', 20);
            $t->string('status')->default('queued')->index();
            $t->string('message_id')->nullable()->unique();
            $t->timestamp('queued_at')->nullable();
            $t->string('error_code')->nullable();
            $t->timestamp('status_at')->nullable();
            $t->timestamps();
            $t->unique(['whatsapp_broadcast_id', 'whatsapp_contact_id']);
        });
    }

    public function down(): void
    {
        foreach (['whatsapp_deliveries', 'whatsapp_broadcasts', 'whatsapp_contacts', 'school_whatsapp_configurations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
