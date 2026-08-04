<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('school_number')->nullable()->after('id');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->unique('school_number');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropUnique(['school_number']);
            $table->dropColumn('school_number');
        });
    }
};