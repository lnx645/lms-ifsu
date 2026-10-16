<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelompok_bank_soals', function (Blueprint $table) {
            $table->string('kode')->nullable()->after('nama');
            $table->enum('urutan_tampil', ['tipe_waktu', 'manual'])->default('tipe_waktu')->after('kode');
            $table->json('komposisi')->nullable()->after('urutan_tampil');
        });
    }

    public function down(): void
    {
        Schema::table('kelompok_bank_soals', function (Blueprint $table) {
            $table->dropColumn(['kode', 'urutan_tampil', 'komposisi']);
        });
    }
};
