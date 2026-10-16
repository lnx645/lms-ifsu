<?php

use App\Models\Soal;
use App\Models\UjianPengerjaan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jawaban siswa per soal dalam satu sesi pengerjaan.
     */
    public function up(): void
    {
        Schema::create('jawaban_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UjianPengerjaan::class)->constrained('ujian_pengerjaans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Soal::class)->constrained('soals')->cascadeOnUpdate()->cascadeOnDelete();
            $table->json('opsi_dipilih')->nullable(); // array id opsi untuk pg/multi/benar_salah
            $table->text('jawaban_teks')->nullable(); // untuk isian/esai
            $table->boolean('benar')->nullable(); // hasil auto-grade objektif; null untuk esai belum dinilai
            $table->float('skor')->nullable();
            $table->timestamps();

            $table->unique(['ujian_pengerjaan_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_siswas');
    }
};
