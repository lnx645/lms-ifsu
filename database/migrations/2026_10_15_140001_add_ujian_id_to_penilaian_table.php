<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Standar akademik sempurna: penilaian CBT terikat langsung ke ujian.
     * 1 ujian = 1 penilaian, tidak bergantung pada nama teks.
     */
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->foreignId('ujian_id')->nullable()->after('tahun_ajaran_id')
                ->constrained('ujians')->nullOnDelete();
            $table->unique(['ujian_id']);
        });
    }

    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropUnique(['ujian_id']);
            $table->dropConstrainedForeignId('ujian_id');
        });
    }
};
