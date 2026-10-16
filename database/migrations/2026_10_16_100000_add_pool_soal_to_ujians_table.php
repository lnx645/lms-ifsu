<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_ujians', function (Blueprint $table) {
            $table->json('pool_soal')->nullable()->after('tata_tertib');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_ujians', function (Blueprint $table) {
            $table->dropColumn('pool_soal');
        });
    }
};
