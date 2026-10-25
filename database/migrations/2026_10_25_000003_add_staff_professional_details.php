<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('title', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('teaching_license_number', 100)->nullable();
            $table->text('academic_qualifications')->nullable();
            $table->string('qualification_institution')->nullable();
            $table->unsignedSmallInteger('qualification_year')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'title', 'date_of_birth', 'gender', 'nationality', 'teaching_license_number',
            'academic_qualifications', 'qualification_institution', 'qualification_year',
        ]));
    }
};
