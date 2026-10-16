<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::rename('bank_opsi_soals', 'cbt_bank_opsi_soals');
            Schema::rename('bank_soals', 'cbt_bank_soals');
            Schema::rename('berita_acara_ujians', 'cbt_berita_acara_ujians');
            Schema::rename('detail_penilaian', 'cbt_detail_penilaian');
            Schema::rename('events', 'cbt_events');
            Schema::rename('group_kelas', 'cbt_group_kelas');
            Schema::rename('groups', 'cbt_groups');
            Schema::rename('jawaban_siswas', 'cbt_jawaban_siswas');
            Schema::rename('kelompok_bank_soals', 'cbt_kelompok_bank_soals');
            Schema::rename('opsi_soals', 'cbt_opsi_soals');
            Schema::rename('paket_soals', 'cbt_paket_soals');
            Schema::rename('pelanggaran_logs', 'cbt_pelanggaran_logs');
            Schema::rename('penilaian', 'cbt_penilaian');
            Schema::rename('penilaian_kelas', 'cbt_penilaian_kelas');
            Schema::rename('periode_ujians', 'cbt_periode_ujians');
            Schema::rename('ruang_ujians', 'cbt_ruang_ujians');
            Schema::rename('sesi_pengawas', 'cbt_sesi_pengawas');
            Schema::rename('sesi_ujian_pesertas', 'cbt_sesi_ujian_pesertas');
            Schema::rename('sesi_ujians', 'cbt_sesi_ujians');
            Schema::rename('soals', 'cbt_soals');
            Schema::rename('ujian_pengerjaans', 'cbt_ujian_pengerjaans');
            Schema::rename('ujians', 'cbt_ujians');
    }

    public function down(): void
    {
            Schema::rename('cbt_ujians', 'ujians');
            Schema::rename('cbt_ujian_pengerjaans', 'ujian_pengerjaans');
            Schema::rename('cbt_soals', 'soals');
            Schema::rename('cbt_sesi_ujians', 'sesi_ujians');
            Schema::rename('cbt_sesi_ujian_pesertas', 'sesi_ujian_pesertas');
            Schema::rename('cbt_sesi_pengawas', 'sesi_pengawas');
            Schema::rename('cbt_ruang_ujians', 'ruang_ujians');
            Schema::rename('cbt_periode_ujians', 'periode_ujians');
            Schema::rename('cbt_penilaian_kelas', 'penilaian_kelas');
            Schema::rename('cbt_penilaian', 'penilaian');
            Schema::rename('cbt_pelanggaran_logs', 'pelanggaran_logs');
            Schema::rename('cbt_paket_soals', 'paket_soals');
            Schema::rename('cbt_opsi_soals', 'opsi_soals');
            Schema::rename('cbt_kelompok_bank_soals', 'kelompok_bank_soals');
            Schema::rename('cbt_jawaban_siswas', 'jawaban_siswas');
            Schema::rename('cbt_groups', 'groups');
            Schema::rename('cbt_group_kelas', 'group_kelas');
            Schema::rename('cbt_events', 'events');
            Schema::rename('cbt_detail_penilaian', 'detail_penilaian');
            Schema::rename('cbt_berita_acara_ujians', 'berita_acara_ujians');
            Schema::rename('cbt_bank_soals', 'bank_soals');
            Schema::rename('cbt_bank_opsi_soals', 'bank_opsi_soals');
    }
};
