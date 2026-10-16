<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemetaan kisi-kisi: setiap soal dikaitkan ke Kompetensi Dasar (KD)
     * dan indikator pencapaian agar sesuai standar penyusunan soal sekolah.
     */
    public function up(): void
    {
        Schema::table('soals', function (Blueprint $table): void {
            $table->string('kode_kd', 50)->nullable()->after('isian_case_sensitive');
            $table->string('indikator', 255)->nullable()->after('kode_kd');
        });

        Schema::table('bank_soals', function (Blueprint $table): void {
            $table->string('kode_kd', 50)->nullable()->after('kesulitan');
            $table->string('indikator', 255)->nullable()->after('kode_kd');
        });
    }

    public function down(): void
    {
        Schema::table('soals', function (Blueprint $table): void {
            $table->dropColumn(['kode_kd', 'indikator']);
        });

        Schema::table('bank_soals', function (Blueprint $table): void {
            $table->dropColumn(['kode_kd', 'indikator']);
        });
    }
};
