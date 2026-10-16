<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CBT v2: kelompok bank soal — wadah bernama berisi kumpulan soal.
     * Alur: buat bank (nama) -> isi soal ke dalamnya.
     */
    public function up(): void
    {
        Schema::create('kelompok_bank_soals', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255);
            $table->text('deskripsi')->nullable();
            $table->foreignId('matpel_id')->constrained('matpels')->cascadeOnDelete();
            $table->foreignId('guru_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('bank_soals', function (Blueprint $table) {
            $table->foreignId('kelompok_bank_soal_id')->nullable()->after('id')
                ->constrained('kelompok_bank_soals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_soals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelompok_bank_soal_id');
        });

        Schema::dropIfExists('kelompok_bank_soals');
    }
};
