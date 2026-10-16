<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Berita acara pelaksanaan ujian per sesi: rekap kehadiran, pelanggaran,
     * dan catatan pengawas sebagai dokumen arsip resmi sekolah.
     */
    public function up(): void
    {
        Schema::create('berita_acara_ujians', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->foreignId('sesi_ujian_id')->nullable()->constrained('sesi_ujians')->nullOnDelete();
            $table->date('tanggal');
            $table->unsignedInteger('jumlah_peserta')->default(0);
            $table->unsignedInteger('jumlah_hadir')->default(0);
            $table->unsignedInteger('jumlah_tidak_hadir')->default(0);
            $table->unsignedInteger('jumlah_pelanggaran')->default(0);
            $table->text('catatan')->nullable();
            $table->string('nama_pengawas', 100)->nullable();
            $table->string('nama_kepala_sekolah', 100)->nullable();
            $table->timestamps();

            $table->unique(['ujian_id', 'sesi_ujian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acara_ujians');
    }
};
