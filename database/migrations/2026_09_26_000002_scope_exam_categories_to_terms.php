<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_categories', function (Blueprint $table) {
            $table->foreignId('term_id')->nullable()->after('school_id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['school_id', 'name']);
            $table->unique(['school_id', 'term_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_categories', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'term_id', 'name']);
            $table->unique(['school_id', 'name']);
            $table->dropConstrainedForeignId('term_id');
        });
    }
};
