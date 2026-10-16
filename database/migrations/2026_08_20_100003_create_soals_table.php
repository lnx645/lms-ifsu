<?php

use App\Models\Ujian;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soal per ujian. Tipe: pg, multi, benar_salah, isian, esai.
     * Kunci `isian` disimpan sebagai JSON daftar jawaban yang diterima.
     */
    public function up(): void
    {
        Schema::create('soals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Ujian::class)->constrained('ujians')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('tipe', 15); // pg, multi, benar_salah, isian, esai
            $table->text('pertanyaan'); // Tiptap HTML
            $table->unsignedSmallInteger('poin')->default(1);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->json('kunci_isian')->nullable(); // daftar jawaban diterima untuk tipe isian
            $table->boolean('isian_case_sensitive')->default(false);
            $table->timestamps();

            $table->index(['ujian_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soals');
    }
};
