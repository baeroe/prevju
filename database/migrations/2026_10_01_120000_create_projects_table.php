<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        // deleting a project keeps its sites, they become standalone again
        Schema::table('sites', fn (Blueprint $table) => $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('sites', fn (Blueprint $table) => $table->dropConstrainedForeignId('project_id'));
        Schema::dropIfExists('projects');
    }
};
