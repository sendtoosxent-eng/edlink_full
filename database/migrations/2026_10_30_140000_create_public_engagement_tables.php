<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_visitors', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->text('ip_address')->nullable(); $t->string('browser',255)->nullable();
            $t->string('referrer_host')->nullable(); $t->string('consent_version',30); $t->timestamp('consented_at'); $t->timestamp('last_seen_at'); $t->timestamps();
        });
        Schema::create('public_visits', function (Blueprint $t) {
            $t->id(); $t->uuid('event_key')->unique(); $t->foreignUuid('public_visitor_id')->constrained()->cascadeOnDelete(); $t->string('path',100); $t->timestamps();
        });
        Schema::create('public_enquiries', function (Blueprint $t) {
            $t->id(); $t->foreignUuid('public_visitor_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name'); $t->string('email')->nullable(); $t->string('phone',30)->nullable(); $t->string('school_name')->nullable(); $t->string('school_location')->nullable();
            $t->string('source',30); $t->string('purpose',30); $t->string('contact_method',20); $t->text('message')->nullable();
            $t->boolean('followup_consent')->default(false); $t->timestamp('consented_at')->nullable(); $t->string('consent_version',30); $t->text('consent_text');
            $t->string('status',30)->default('new')->index(); $t->text('notes')->nullable(); $t->timestamp('closed_at')->nullable(); $t->timestamp('opted_out_at')->nullable(); $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('public_enquiries'); Schema::dropIfExists('public_visits'); Schema::dropIfExists('public_visitors');
    }
};
