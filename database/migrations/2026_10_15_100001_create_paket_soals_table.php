<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CBT v2: paket soal (varian A/B/C) untuk ujian resmi.
     */
    public function up(): void
    {
        Schema::create('paket_soals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->string('kode', 10); // A, B, C, ...
            $table->string('nama')->nullable(); // "Paket A — Reguler"
            $table->timestamps();

            $table->unique(['ujian_id', 'kode']);
        });

        // Soal milik paket (bukan langsung milik ujian, untuk ujian berpake).
        Schema::table('soals', function (Blueprint $table) {
            $table->foreignId('paket_soal_id')->nullable()->after('ujian_id')
                ->constrained('paket_soals')->nullOnDelete();
        });

        // Pengerjaan mencatat paket yang dikerjakan siswa.
        Schema::table('ujian_pengerjaans', function (Blueprint $table) {
            $table->foreignId('paket_soal_id')->nullable()->after('ujian_id')
                ->constrained('paket_soals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ujian_pengerjaans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paket_soal_id');
        });

        Schema::table('soals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paket_soal_id');
        });

        Schema::dropIfExists('paket_soals');
    }
};
