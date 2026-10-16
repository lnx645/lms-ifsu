<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tata tertib pengerjaan ujian, ditampilkan ke siswa sebelum mulai.
     */
    public function up(): void
    {
        Schema::table('ujians', function (Blueprint $table): void {
            $table->text('tata_tertib')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('ujians', function (Blueprint $table): void {
            $table->dropColumn('tata_tertib');
        });
    }
};
