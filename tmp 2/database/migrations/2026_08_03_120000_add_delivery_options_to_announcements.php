<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->boolean('send_email')->default(false)->after('target_audience');
            $table->boolean('send_sms')->default(false)->after('send_email');
            $table->string('delivery_status')->default('queued')->after('send_sms');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', fn (Blueprint $table) => $table->dropColumn([
            'send_email', 'send_sms', 'delivery_status',
        ]));
    }
};
