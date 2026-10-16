<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menautkan tiap pengerjaan ke sesi gelombang yang sedang aktif
     * (null bila ujian tidak memakai sesi).
     */
    public function up(): void
    {
        Schema::table('ujian_pengerjaans', function (Blueprint $table): void {
            $table->foreignId('sesi_ujian_id')->nullable()->constrained('sesi_ujians')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ujian_pengerjaans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sesi_ujian_id');
        });
    }
};
