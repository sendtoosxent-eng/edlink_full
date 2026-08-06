<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->unsignedTinyInteger('term_number')->nullable()->after('name');
            $table->index(['school_id', 'year', 'term_number'], 'terms_school_year_number_index');
        });

        DB::table('terms')->orderBy('id')->get(['id', 'name'])->each(function ($term): void {
            if (preg_match('/(?:term|semester)\s*([123])/i', (string) $term->name, $matches)) {
                DB::table('terms')->where('id', $term->id)->update(['term_number' => (int) $matches[1]]);
            }
        });

        Schema::create('graduation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->restrictOnDelete();
            $table->unsignedSmallInteger('graduation_year');
            $table->date('graduated_at');
            $table->decimal('final_average', 5, 2)->default(0);
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->string('certificate_number')->unique();
            $table->string('portal_access')->default('read_only');
            $table->foreignId('graduated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'term_id']);
            $table->index(['school_id', 'graduation_year', 'reversed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_records');
        Schema::table('terms', function (Blueprint $table) {
            $table->dropIndex('terms_school_year_number_index');
            $table->dropColumn('term_number');
        });
    }
};
