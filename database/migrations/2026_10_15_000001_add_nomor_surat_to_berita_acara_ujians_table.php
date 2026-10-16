<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor surat berita acara agar layak arsip resmi sekolah.
     */
    public function up(): void
    {
        Schema::table('berita_acara_ujians', function (Blueprint $table): void {
            $table->string('nomor_surat', 100)->nullable()->after('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('berita_acara_ujians', function (Blueprint $table): void {
            $table->dropColumn('nomor_surat');
        });
    }
};
