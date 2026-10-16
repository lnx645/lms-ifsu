<?php

use App\Models\Soal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opsi jawaban untuk soal pilihan ganda / multi-jawaban / benar-salah.
     */
    public function up(): void
    {
        Schema::create('opsi_soals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Soal::class)->constrained('soals')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('teks');
            $table->boolean('benar')->default(false);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['soal_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opsi_soals');
    }
};
