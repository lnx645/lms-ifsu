<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran media pada soal (gambar/audio) untuk soal bacaan bergambar,
     * listening, dsb. Disimpan di disk public: soal-media/.
     */
    public function up(): void
    {
        Schema::table('soals', function (Blueprint $table): void {
            $table->string('media_path', 255)->nullable()->after('indikator');
            $table->string('media_type', 20)->nullable()->after('media_path');
        });

        Schema::table('bank_soals', function (Blueprint $table): void {
            $table->string('media_path', 255)->nullable()->after('indikator');
            $table->string('media_type', 20)->nullable()->after('media_path');
        });
    }

    public function down(): void
    {
        Schema::table('soals', function (Blueprint $table): void {
            $table->dropColumn(['media_path', 'media_type']);
        });

        Schema::table('bank_soals', function (Blueprint $table): void {
            $table->dropColumn(['media_path', 'media_type']);
        });
    }
};
