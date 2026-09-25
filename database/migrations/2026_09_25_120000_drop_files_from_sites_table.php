<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // files on disk are the only truth since the Filament upload field is gone
    public function up(): void
    {
        Schema::table('sites', fn (Blueprint $table) => $table->dropColumn('files'));
    }

    public function down(): void
    {
        Schema::table('sites', fn (Blueprint $table) => $table->json('files')->nullable());
    }
};
