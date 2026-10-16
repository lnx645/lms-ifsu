<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_penilaian', function (Blueprint $table) {
            $table->foreignId('guru_kelas_id')
                ->nullable()
                ->after('penilaian_id')
                ->constrained('guru_kelas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['penilaian_id', 'guru_kelas_id', 'siswa_nisn'], 'detail_penilaian_penilaian_gk_siswa_unique');
        });
    }

    public function down(): void
    {
        Schema::table('detail_penilaian', function (Blueprint $table) {
            $table->dropUnique('detail_penilaian_penilaian_gk_siswa_unique');
            $table->dropConstrainedForeignId('guru_kelas_id');
        });
    }
};
