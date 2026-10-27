<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desktop_releases', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->default('windows-x64');
            $table->string('version', 50);
            $table->string('path');
            $table->string('filename');
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->timestamp('published_at')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['platform', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desktop_releases');
    }
};
