<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            try { Schema::table('cbt_bank_soals', fn ($t) => $t->index('kelompok_bank_soal_id', 'idx_cbt_bank_soals_kelompok_bank_soal_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_bank_soals', fn ($t) => $t->index('matpel_id', 'idx_cbt_bank_soals_matpel_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_bank_soals', fn ($t) => $t->index('guru_id', 'idx_cbt_bank_soals_guru_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_bank_soals', fn ($t) => $t->index('tipe', 'idx_cbt_bank_soals_tipe')); } catch (\Exception $e) {}
            try { Schema::table('cbt_bank_opsi_soals', fn ($t) => $t->index('bank_soal_id', 'idx_cbt_bank_opsi_soals_bank_soal_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_kelompok_bank_soals', fn ($t) => $t->index('matpel_id', 'idx_cbt_kelompok_bank_soals_matpel_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_kelompok_bank_soals', fn ($t) => $t->index('guru_id', 'idx_cbt_kelompok_bank_soals_guru_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_soals', fn ($t) => $t->index('bank_soal_id', 'idx_cbt_soals_bank_soal_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_opsi_soals', fn ($t) => $t->index('soal_id', 'idx_cbt_opsi_soals_soal_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujians', fn ($t) => $t->index('kelas_id', 'idx_cbt_ujians_kelas_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujians', fn ($t) => $t->index('matpel_id', 'idx_cbt_ujians_matpel_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujians', fn ($t) => $t->index('guru_id', 'idx_cbt_ujians_guru_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_sesi_ujians', fn ($t) => $t->index('ujian_id', 'idx_cbt_sesi_ujians_ujian_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_sesi_ujian_pesertas', fn ($t) => $t->index('sesi_ujian_id', 'idx_cbt_sesi_ujian_pesertas_sesi_ujian_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_sesi_ujian_pesertas', fn ($t) => $t->index('siswa_id', 'idx_cbt_sesi_ujian_pesertas_siswa_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujian_pengerjaans', fn ($t) => $t->index('ujian_id', 'idx_cbt_ujian_pengerjaans_ujian_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujian_pengerjaans', fn ($t) => $t->index('siswa_id', 'idx_cbt_ujian_pengerjaans_siswa_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_jawaban_siswas', fn ($t) => $t->index('pengerjaan_id', 'idx_cbt_jawaban_siswas_pengerjaan_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_events', fn ($t) => $t->index('created_at', 'idx_cbt_events_created_at')); } catch (\Exception $e) {}
            try { Schema::table('cbt_groups', fn ($t) => $t->index('matpel_id', 'idx_cbt_groups_matpel_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_groups', fn ($t) => $t->index('guru_id', 'idx_cbt_groups_guru_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_group_kelas', fn ($t) => $t->index('group_id', 'idx_cbt_group_kelas_group_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_group_kelas', fn ($t) => $t->index('kelas_id', 'idx_cbt_group_kelas_kelas_id')); } catch (\Exception $e) {}
    }

    public function down(): void
    {
            try { Schema::table('cbt_bank_soals', fn ($t) => $t->dropIndex('idx_cbt_bank_soals_kelompok_bank_soal_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_bank_soals', fn ($t) => $t->dropIndex('idx_cbt_bank_soals_matpel_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_bank_soals', fn ($t) => $t->dropIndex('idx_cbt_bank_soals_guru_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_bank_soals', fn ($t) => $t->dropIndex('idx_cbt_bank_soals_tipe')); } catch (\Exception $e) {}
            try { Schema::table('cbt_bank_opsi_soals', fn ($t) => $t->dropIndex('idx_cbt_bank_opsi_soals_bank_soal_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_kelompok_bank_soals', fn ($t) => $t->dropIndex('idx_cbt_kelompok_bank_soals_matpel_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_kelompok_bank_soals', fn ($t) => $t->dropIndex('idx_cbt_kelompok_bank_soals_guru_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_soals', fn ($t) => $t->dropIndex('idx_cbt_soals_bank_soal_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_opsi_soals', fn ($t) => $t->dropIndex('idx_cbt_opsi_soals_soal_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujians', fn ($t) => $t->dropIndex('idx_cbt_ujians_kelas_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujians', fn ($t) => $t->dropIndex('idx_cbt_ujians_matpel_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujians', fn ($t) => $t->dropIndex('idx_cbt_ujians_guru_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_sesi_ujians', fn ($t) => $t->dropIndex('idx_cbt_sesi_ujians_ujian_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_sesi_ujian_pesertas', fn ($t) => $t->dropIndex('idx_cbt_sesi_ujian_pesertas_sesi_ujian_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_sesi_ujian_pesertas', fn ($t) => $t->dropIndex('idx_cbt_sesi_ujian_pesertas_siswa_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujian_pengerjaans', fn ($t) => $t->dropIndex('idx_cbt_ujian_pengerjaans_ujian_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_ujian_pengerjaans', fn ($t) => $t->dropIndex('idx_cbt_ujian_pengerjaans_siswa_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_jawaban_siswas', fn ($t) => $t->dropIndex('idx_cbt_jawaban_siswas_pengerjaan_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_events', fn ($t) => $t->dropIndex('idx_cbt_events_created_at')); } catch (\Exception $e) {}
            try { Schema::table('cbt_groups', fn ($t) => $t->dropIndex('idx_cbt_groups_matpel_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_groups', fn ($t) => $t->dropIndex('idx_cbt_groups_guru_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_group_kelas', fn ($t) => $t->dropIndex('idx_cbt_group_kelas_group_id')); } catch (\Exception $e) {}
            try { Schema::table('cbt_group_kelas', fn ($t) => $t->dropIndex('idx_cbt_group_kelas_kelas_id')); } catch (\Exception $e) {}
    }
};
