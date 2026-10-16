<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guru_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Guru::class)->constrained('gurus')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignIdFor(Kelas::class)->constrained('kelas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignIdFor(TahunAjaran::class)->constrained('tahun_ajaran')->cascadeOnUpdate()->restrictOnDelete();
            $table->boolean('aktif')->default(true);
            $table->char('kode_undangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guru_kelas');
    }
};
