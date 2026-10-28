<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // one row per page view or password attempt, either on a site or on a project's client page
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('path', 1024)->nullable();
            $table->string('visitor', 16);
            $table->string('device', 10);
            $table->string('browser', 30)->nullable();
            $table->string('os', 30)->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['site_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
