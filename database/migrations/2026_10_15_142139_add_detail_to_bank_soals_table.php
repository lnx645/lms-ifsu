<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_soals', function (Blueprint $table) {
            // Detail kompleks per tipe soal ala Extraordinary (JSON)
            // - pilihan_ganda: { opsi: [{teks}], kunci: 'a' }
            // - kompleks: { opsi: [{teks}], kunci: ['a','c'] }
            // - menjodohkan: { pasangan: [{kiri, kanan}] }
            // - mengurutkan: { urutan: [...] }
            // - benar_salah/setuju_tidak: { pernyataan: [{teks, jawaban}] }
            // - matrix: { rows, cols, kunci }
            // - mengelompokkan: { kelompok: [{nama, items}] }
            // - blank_space: { teks, kunci: [] }
            // - image_labeling: { image, markers: [{x, y, label, kunci}] }
            // - listening: { audio }
            $table->json('detail')->nullable()->after('kunci_isian');
        });
    }

    public function down(): void
    {
        Schema::table('bank_soals', function (Blueprint $table) {
            $table->dropColumn('detail');
        });
    }
};
