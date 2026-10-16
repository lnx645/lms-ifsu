<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugases', function (Blueprint $table) {
            $table->foreignId('penilaian_id')
                ->nullable()
                ->after('poin')
                ->constrained('penilaian')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tugases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penilaian_id');
        });
    }
};
