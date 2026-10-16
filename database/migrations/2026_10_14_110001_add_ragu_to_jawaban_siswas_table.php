<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tandai jawaban yang ragu-ragu (ala ujian konvensional/UNBK).
     */
    public function up(): void
    {
        Schema::table('jawaban_siswas', function (Blueprint $table): void {
            $table->boolean('ragu')->default(false)->after('skor');
        });
    }

    public function down(): void
    {
        Schema::table('jawaban_siswas', function (Blueprint $table): void {
            $table->dropColumn('ragu');
        });
    }
};
