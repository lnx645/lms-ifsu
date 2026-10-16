<?php

use App\Models\Ujian;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sesi pengerjaan ujian oleh seorang siswa (satu attempt).
     */
    public function up(): void
    {
        Schema::create('ujian_pengerjaans', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Ujian::class)->constrained('ujians')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('siswa_nisn', 10);
            $table->unsignedSmallInteger('attempt_ke')->default(1);
            $table->timestamp('mulai_at');
            $table->timestamp('batas_at'); // mulai_at + durasi, ditegakkan server
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 15)->default('berlangsung'); // berlangsung, selesai, auto_submit, diskualifikasi
            $table->float('nilai_objektif')->nullable();
            $table->float('nilai_esai')->nullable();
            $table->float('nilai_total')->nullable();
            $table->unsignedSmallInteger('jumlah_pelanggaran')->default(0);
            $table->timestamps();

            $table->unique(['ujian_id', 'siswa_nisn', 'attempt_ke']);
            $table->index(['ujian_id', 'status']);

            $table->foreign('siswa_nisn')->references('nisn')->on('siswa')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_pengerjaans');
    }
};
