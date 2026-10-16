<?php

use App\Models\UjianPengerjaan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log pelanggaran proctoring (blur, keluar fullscreen, copy, paste, contextmenu).
     */
    public function up(): void
    {
        Schema::create('pelanggaran_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UjianPengerjaan::class)->constrained('ujian_pengerjaans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('jenis', 20); // blur, exit_fullscreen, copy, paste, contextmenu
            $table->timestamp('terjadi_at');
            $table->timestamps();

            $table->index('ujian_pengerjaan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggaran_logs');
    }
};
