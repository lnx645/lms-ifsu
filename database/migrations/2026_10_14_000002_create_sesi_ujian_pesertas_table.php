<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penjadwalan siswa ke sesi ujian ala ANBK: satu siswa dijatahkan
     * ke tepat satu sesi per ujian (mode_sesi = 'terjadwal').
     */
    public function up(): void
    {
        Schema::create('sesi_ujian_pesertas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sesi_ujian_id')->constrained('sesi_ujians')->cascadeOnDelete();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->string('siswa_nisn', 10);
            $table->foreign('siswa_nisn')->references('nisn')->on('siswa')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ujian_id', 'siswa_nisn']);
            $table->unique(['sesi_ujian_id', 'siswa_nisn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_ujian_pesertas');
    }
};
