<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [];

        if (Schema::hasColumn('guru_kelas', 'active_forum')) {
            $columns[] = 'active_forum';
        }

        if (Schema::hasColumn('guru_kelas', 'lihat_anggota_kelas')) {
            $columns[] = 'lihat_anggota_kelas';
        }

        if ($columns) {
            Schema::table('guru_kelas', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        Schema::table('guru_kelas', function (Blueprint $table) {
            $table->boolean('active_forum')->default(false)->after('aktif');
            $table->boolean('lihat_anggota_kelas')->default(false)->after('active_forum');
        });
    }
};
