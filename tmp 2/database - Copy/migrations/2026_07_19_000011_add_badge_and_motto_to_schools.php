<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('schools',fn(Blueprint $table)=>[$table->string('badge_path')->nullable(),$table->string('motto')->nullable()]);}public function down():void{Schema::table('schools',fn(Blueprint $table)=>$table->dropColumn(['badge_path','motto']));}};
