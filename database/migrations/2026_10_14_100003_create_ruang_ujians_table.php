<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ruang ujian fisik tempat sesi dilaksanakan (untuk kartu peserta,
     * daftar hadir, dan berita acara).
     */
    public function up(): void
    {
        Schema::create('ruang_ujians', function (Blueprint $table): void {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 100);
            $table->unsignedInteger('kapasitas')->default(0);
            $table->string('lokasi', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('sesi_ujians', function (Blueprint $table): void {
            $table->foreignId('ruang_ujian_id')->nullable()->after('kapasitas')
                ->constrained('ruang_ujians')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sesi_ujians', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ruang_ujian_id');
        });

        Schema::dropIfExists('ruang_ujians');
    }
};
