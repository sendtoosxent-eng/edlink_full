<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Bio data
            $table->date('date_of_birth')->nullable()->after('admission_no');
            $table->string('gender')->nullable()->after('date_of_birth'); // male | female
            $table->date('admission_date')->nullable()->after('gender');
            $table->string('photo_path')->nullable()->after('admission_date');

            // Social data
            $table->string('nationality')->nullable()->after('photo_path');
            $table->string('religion')->nullable()->after('nationality');
            $table->string('blood_group')->nullable()->after('religion');
            $table->text('home_address')->nullable()->after('blood_group');
            $table->text('medical_notes')->nullable()->after('home_address');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth', 'gender', 'admission_date', 'photo_path',
                'nationality', 'religion', 'blood_group', 'home_address', 'medical_notes',
            ]);
        });
    }
};
