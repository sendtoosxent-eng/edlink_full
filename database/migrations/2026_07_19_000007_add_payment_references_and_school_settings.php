<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('fee_payments', function (Blueprint $table) { $table->string('transaction_id')->nullable()->after('method'); $table->string('bank_slip_number')->nullable()->after('transaction_id'); }); Schema::table('schools', function (Blueprint $table) { $table->string('email')->nullable(); $table->string('phone')->nullable(); $table->string('address')->nullable(); }); } public function down(): void { Schema::table('fee_payments', function (Blueprint $table) { $table->dropColumn(['transaction_id','bank_slip_number']); }); Schema::table('schools', function (Blueprint $table) { $table->dropColumn(['email','phone','address']); }); } };
