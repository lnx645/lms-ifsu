<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->index(['tahun_ajaran_id', 'active']);
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
        });

        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->dropIndex(['tahun_ajaran_id', 'active']);
        });
    }
};
