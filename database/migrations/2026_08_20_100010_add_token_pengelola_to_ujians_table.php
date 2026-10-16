<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Siapa yang berwenang membuat & merilis token ujian: 'guru' (pengampu) atau 'admin'.
     * Default 'guru' agar perilaku lama tetap.
     */
    public function up(): void
    {
        Schema::table('ujians', function (Blueprint $table) {
            $table->string('token_pengelola', 10)->default('guru')->after('token_released_at');
        });
    }

    public function down(): void
    {
        Schema::table('ujians', function (Blueprint $table) {
            $table->dropColumn('token_pengelola');
        });
    }
};
