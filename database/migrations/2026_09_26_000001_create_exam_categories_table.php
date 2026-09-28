<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 5, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('exam_category_id')->nullable()->after('term_id')->constrained('exam_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_category_id');
        });
        Schema::dropIfExists('exam_categories');
    }
};
