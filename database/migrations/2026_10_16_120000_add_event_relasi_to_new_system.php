<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. groups -> event_id
        Schema::table('cbt_groups', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained('cbt_events')->nullOnDelete();
        });

        // 2. ujians -> event_id + kelompok_bank_soal_id
        Schema::table('cbt_ujians', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained('cbt_events')->nullOnDelete();
            $table->foreignId('kelompok_bank_soal_id')->nullable()->after('event_id')->constrained('cbt_kelompok_bank_soals')->nullOnDelete();
        });

        // 3. sesi_ujians -> kapasitas + terisi
        Schema::table('cbt_sesi_ujians', function (Blueprint $table) {
            $table->integer('kapasitas')->default(40)->after('ruang_ujian_id');
            $table->integer('terisi')->default(0)->after('kapasitas');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_sesi_ujians', function (Blueprint $table) {
            $table->dropColumn(['kapasitas', 'terisi']);
        });
        Schema::table('cbt_ujians', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
            $table->dropConstrainedForeignId('kelompok_bank_soal_id');
        });
        Schema::table('cbt_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
