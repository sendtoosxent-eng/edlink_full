<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::table('cash_pool_entries',fn(Blueprint $table)=>$table->foreignId('payroll_run_id')->nullable()->after('expense_id')->constrained('payroll_runs')->cascadeOnDelete()->unique());} public function down():void{Schema::table('cash_pool_entries',fn(Blueprint $table)=>$table->dropConstrainedForeignId('payroll_run_id'));} };
