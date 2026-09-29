<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('student_category_id')->nullable()->after('stream_id')->constrained()->nullOnDelete();
            $table->foreignId('term_id')->nullable()->after('student_category_id')->constrained()->nullOnDelete();
            $table->string('status')->default('active')->after('term_id'); // active | inactive
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_category_id');
            $table->dropConstrainedForeignId('term_id');
            $table->dropColumn('status');
        });
    }
};
