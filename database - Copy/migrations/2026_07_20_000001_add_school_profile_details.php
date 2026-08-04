<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('schools',function(Blueprint $table){$table->string('website')->nullable();$table->string('principal_name')->nullable();});}public function down():void{Schema::table('schools',fn(Blueprint $table)=>$table->dropColumn(['website','principal_name']));}};
