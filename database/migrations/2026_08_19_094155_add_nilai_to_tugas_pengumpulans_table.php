<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas_pengumpulans', function (Blueprint $table) {
            $table->decimal('nilai', 5, 2)->nullable()->after('jawaban_teks');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_pengumpulans', function (Blueprint $table) {
            $table->dropColumn('nilai');
        });
    }
};
