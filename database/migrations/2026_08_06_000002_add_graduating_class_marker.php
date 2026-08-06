<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', fn (Blueprint $table) => $table->boolean('is_graduating_class')->default(false)->after('sort_order'));
    }

    public function down(): void
    {
        Schema::table('school_classes', fn (Blueprint $table) => $table->dropColumn('is_graduating_class'));
    }
};
