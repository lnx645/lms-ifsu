<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penugasan pengawas (guru atau nama bebas) per sesi ujian.
     */
    public function up(): void
    {
        Schema::create('sesi_pengawas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sesi_ujian_id')->constrained('sesi_ujians')->cascadeOnDelete();
            $table->foreignId('guru_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->string('nama_pengawas', 100);
            $table->string('peran', 20)->default('pengawas');
            $table->timestamps();

            $table->unique(['sesi_ujian_id', 'guru_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_pengawas');
    }
};
