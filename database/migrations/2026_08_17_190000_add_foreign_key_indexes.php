<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PostgreSQL tidak membuat index otomatis untuk foreign key, sehingga
     * kolom FK pada tabel yang sering di-join/query perlu index eksplisit.
     */
    public function up(): void
    {
        if (Schema::hasTable('guru_kelas')) {
            Schema::table('guru_kelas', function (Blueprint $table) {
                $table->index('guru_id');
                $table->index('kelas_id');
                $table->index('matpel_id');
                $table->index('tahun_ajaran_id');
                $table->index(['tahun_ajaran_id', 'aktif']);
            });
        }

        if (Schema::hasTable('jadwal_pelajarans')) {
            Schema::table('jadwal_pelajarans', function (Blueprint $table) {
                $table->index('guru_id');
                $table->index('matpel_id');
                $table->index('kelas_id');
                $table->index('jam_pelajaran_id');
                $table->index(['hari', 'jam_pelajaran_id']);
            });
        }

        if (Schema::hasTable('kelas')) {
            Schema::table('kelas', function (Blueprint $table) {
                if (! Schema::hasColumn('kelas', 'jurusan_id')) {
                    return;
                }

                $table->index('jurusan_id');
                $table->index('guru_id');
            });
        }

        if (Schema::hasTable('siswa_kelas')) {
            Schema::table('siswa_kelas', function (Blueprint $table) {
                $table->index('kelas_id');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role');
                $table->index('guru_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['role']);
                $table->dropIndex(['guru_id']);
            });
        }

        if (Schema::hasTable('siswa_kelas')) {
            Schema::table('siswa_kelas', function (Blueprint $table) {
                $table->dropIndex(['kelas_id']);
            });
        }

        if (Schema::hasTable('kelas')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->dropIndex(['jurusan_id']);
                $table->dropIndex(['guru_id']);
            });
        }

        if (Schema::hasTable('jadwal_pelajarans')) {
            Schema::table('jadwal_pelajarans', function (Blueprint $table) {
                $table->dropIndex(['guru_id']);
                $table->dropIndex(['matpel_id']);
                $table->dropIndex(['kelas_id']);
                $table->dropIndex(['jam_pelajaran_id']);
                $table->dropIndex(['hari', 'jam_pelajaran_id']);
            });
        }

        if (Schema::hasTable('guru_kelas')) {
            Schema::table('guru_kelas', function (Blueprint $table) {
                $table->dropIndex(['guru_id']);
                $table->dropIndex(['kelas_id']);
                $table->dropIndex(['matpel_id']);
                $table->dropIndex(['tahun_ajaran_id']);
                $table->dropIndex(['tahun_ajaran_id', 'aktif']);
            });
        }
    }
};
