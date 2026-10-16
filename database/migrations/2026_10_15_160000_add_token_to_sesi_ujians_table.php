<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_sesi_ujians', function (Blueprint $table): void {
            $table->string('token', 20)->nullable()->after('ruang_ujian_id');
            $table->timestamp('token_released_at')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_sesi_ujians', function (Blueprint $table): void {
            $table->dropColumn(['token', 'token_released_at']);
        });
    }
};
