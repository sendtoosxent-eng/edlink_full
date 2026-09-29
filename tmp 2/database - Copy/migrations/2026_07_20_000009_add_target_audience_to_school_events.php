<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('school_events',fn(Blueprint $table)=>$table->string('target_audience')->default('all')->after('type'));}public function down():void{Schema::table('school_events',fn(Blueprint $table)=>$table->dropColumn('target_audience'));}};
