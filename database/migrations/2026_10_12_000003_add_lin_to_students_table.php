<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('lin', 100)->nullable()->after('admission_no');
            $table->unique(['school_id', 'lin'], 'students_school_lin_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_school_lin_unique');
            $table->dropColumn('lin');
        });
    }
};
