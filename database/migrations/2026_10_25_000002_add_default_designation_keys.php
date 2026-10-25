<?php

use App\Models\School;
use App\Services\DefaultDesignationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->string('default_key', 50)->nullable();
            $table->unique(['school_id', 'default_key']);
        });

        School::query()->each(fn (School $school) => app(DefaultDesignationService::class)->ensureFor($school));
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'default_key']);
            $table->dropColumn('default_key');
        });
    }
};
