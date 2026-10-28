<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('desktop_releases', fn (Blueprint $table) => $table->string('sha512', 88)->nullable());
    }

    public function down(): void
    {
        Schema::table('desktop_releases', fn (Blueprint $table) => $table->dropColumn('sha512'));
    }
};
