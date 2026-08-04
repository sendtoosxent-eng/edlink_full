<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('status',20)->default('new')->after('type');
            $table->timestamp('read_at')->nullable()->after('status');
            $table->timestamp('closed_at')->nullable()->after('read_at');
            $table->index(['status','created_at']);
        });
        Schema::create('contact_message_replies', function (Blueprint $table) {
            $table->id(); $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_admin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject'); $table->text('message'); $table->string('delivery_status',20)->default('sent');
            $table->text('delivery_error')->nullable(); $table->timestamp('sent_at')->nullable(); $table->timestamps();
            $table->index(['contact_message_id','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('contact_message_replies'); Schema::table('contact_messages',fn(Blueprint $table)=>$table->dropColumn(['status','read_at','closed_at'])); }
};