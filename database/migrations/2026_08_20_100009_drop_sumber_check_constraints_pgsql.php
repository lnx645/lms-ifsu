<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Di PostgreSQL, kolom `enum` lama meninggalkan CHECK constraint bernama
     * (mis. penilaian_sumber_check) yang tidak ikut terhapus saat kolom di-`change()`
     * menjadi string. Akibatnya nilai kategori CBT (kuis/uts/uas/usbk) tetap ditolak.
     * Migrasi ini men-drop constraint tersebut secara eksplisit. Hanya relevan untuk
     * PostgreSQL; SQLite/pengujian tidak memiliki named check constraint ini.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['penilaian', 'detail_penilaian'] as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_sumber_check");
            }
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan CHECK constraint karena kolom kini bertipe string bebas.
    }
};
