<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Password khusus ujian untuk siswa (terpisah dari password akun harian).
     * Dicetak di kartu peserta, bisa di-reset panitia.
     */
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('password_ujian')->nullable()->after('nisn');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('password_ujian');
        });
    }
};
