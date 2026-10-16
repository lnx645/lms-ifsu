<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Penilaian;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ujian CBT (kuis / uts / uas / usbk) yang berlabuh pada penugasan guru_kelas.
     */
    public function up(): void
    {
        Schema::create('ujians', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Guru::class)->constrained('gurus')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignIdFor(GuruKelas::class)->constrained('guru_kelas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Penilaian::class)->nullable()->constrained('penilaian')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('dibuat_oleh_admin')->default(false);
            $table->string('kategori', 10)->default('kuis'); // kuis, uts, uas, usbk
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->timestamp('tanggal_mulai')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->unsignedSmallInteger('durasi_menit')->default(60);
            $table->unsignedSmallInteger('maks_attempt')->default(1);
            $table->boolean('acak_soal')->default(false);
            $table->boolean('acak_opsi')->default(false);
            $table->boolean('tampilkan_hasil')->default(true);
            $table->boolean('wajib_fullscreen')->default(false);
            $table->unsignedSmallInteger('maks_pelanggaran')->default(0); // 0 = tidak auto-diskualifikasi
            $table->string('token', 8)->nullable();
            $table->timestamp('token_released_at')->nullable();
            $table->unsignedSmallInteger('nilai_maks')->default(100);
            $table->unsignedSmallInteger('bobot')->default(1);
            $table->string('status', 10)->default('draft'); // draft, terbit
            $table->timestamps();

            $table->index(['guru_kelas_id', 'kategori', 'status']);
            $table->index('guru_id');
            $table->index('penilaian_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujians');
    }
};
