<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perluas kolom `sumber` menjadi string agar dapat menampung kategori CBT
     * (kuis/uts/uas/usbk) selain nilai lama (manual/tugas/cbt). Enum CHECK
     * constraint sebelumnya menolak nilai kategori ujian.
     */
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->string('sumber', 20)->default('manual')->change();
        });

        Schema::table('detail_penilaian', function (Blueprint $table) {
            $table->string('sumber', 20)->default('manual')->change();
        });
    }

    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->enum('sumber', ['manual', 'tugas'])->default('manual')->change();
        });

        Schema::table('detail_penilaian', function (Blueprint $table) {
            $table->enum('sumber', ['manual', 'tugas', 'cbt'])->default('manual')->change();
        });
    }
};
