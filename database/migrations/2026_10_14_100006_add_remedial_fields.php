<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remedial: KKM per ujian dan penanda attempt remedial pada pengerjaan.
     */
    public function up(): void
    {
        Schema::table('ujians', function (Blueprint $table): void {
            $table->unsignedTinyInteger('kkm')->nullable()->after('nilai_maks');
        });

        Schema::table('ujian_pengerjaans', function (Blueprint $table): void {
            $table->boolean('is_remedial')->default(false)->after('sesi_ujian_id');
        });
    }

    public function down(): void
    {
        Schema::table('ujian_pengerjaans', function (Blueprint $table): void {
            $table->dropColumn('is_remedial');
        });

        Schema::table('ujians', function (Blueprint $table): void {
            $table->dropColumn('kkm');
        });
    }
};
