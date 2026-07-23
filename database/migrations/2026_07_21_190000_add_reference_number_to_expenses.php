<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('reference_number', 100)->nullable()->after('expense_date');
            $table->unique(['school_id', 'reference_number'], 'expenses_school_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_school_reference_unique');
            $table->dropColumn('reference_number');
        });
    }
};