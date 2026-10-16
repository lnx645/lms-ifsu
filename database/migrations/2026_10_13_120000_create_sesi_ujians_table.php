<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gelombang sesi ujian ala standar nasional (Sesi 1, Sesi 2, ...).
     * Ujian tanpa baris di tabel ini berjalan seperti biasa (tanpa sesi).
     */
    public function up(): void
    {
        Schema::create('sesi_ujians', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->string('nama', 50);
            $table->unsignedInteger('urutan')->default(0);
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->unsignedInteger('kapasitas')->nullable();
            $table->timestamps();

            $table->index(['ujian_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_ujians');
    }
};
