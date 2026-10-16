<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mode pembagian sesi ujian ala ANBK:
     * - bebas: siswa boleh masuk sesi aktif mana saja (rebutan, kapasitas first-come).
     * - terjadwal: siswa hanya boleh masuk sesi yang sudah dijatahkan untuknya.
     */
    public function up(): void
    {
        Schema::table('ujians', function (Blueprint $table): void {
            $table->string('mode_sesi', 20)->default('bebas')->after('token_pengelola');
        });
    }

    public function down(): void
    {
        Schema::table('ujians', function (Blueprint $table): void {
            $table->dropColumn('mode_sesi');
        });
    }
};
