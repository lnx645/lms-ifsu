<?php

namespace App\Services;

use App\Models\Ujian;

/**
 * CBT v2: validasi kelengkapan ujian resmi sebelum terbit.
 *
 * Ujian kategori besar (uts/uas/usbk) wajib memenuhi checklist:
 * paket soal, sesi + ruang, token aktif, tata tertib, dan KKM.
 * Kuis/UH (mode santai) hanya butuh minimal 1 soal.
 */
class KelengkapanUjianService
{
    /**
     * @return array<string> daftar masalah; kosong bila lengkap.
     */
    public function cek(Ujian $ujian): array
    {
        $masalah = [];

        // Mode santai: cukup ada soal.
        if (! $ujian->kategoriBesar()) {
            $jumlahSoal = $ujian->memakaiPaketSoal()
                ? $ujian->paketSoals()->withCount('soals')->get()->sum('soals_count')
                : $ujian->soals()->whereNull('paket_soal_id')->count();

            if ($jumlahSoal === 0) {
                $masalah[] = 'Tambahkan minimal satu soal sebelum menerbitkan.';
            }

            return $masalah;
        }

        // Mode resmi: checklist ketat.
        if (! $ujian->memakaiPaketSoal()) {
            $masalah[] = 'PAKET:Buat paket soal (minimal 2 paket, misal A dan B)';
        } else {
            $paketKosong = $ujian->paketSoals()->withCount('soals')->get()->filter(fn ($p) => $p->soals_count === 0);
            if ($paketKosong->isNotEmpty()) {
                $masalah[] = 'PAKET:Paket '.$paketKosong->pluck('kode')->join(', ').' belum ada soalnya — kembalikan ke guru';
            }
        }

        if (! $ujian->memakaiSesi()) {
            $masalah[] = 'SESI:Atur sesi ujian (gelombang) beserta waktunya';
        } else {
            $tanpaRuang = $ujian->sesis()->whereNull('ruang_ujian_id')->count();
            if ($tanpaRuang > 0) {
                $masalah[] = "SESI:{$tanpaRuang} sesi belum ditentukan ruangnya";
            }
            $tanpaToken = $ujian->sesis()->whereNull('token')->count();
            if ($tanpaToken > 0) {
                $masalah[] = "SESI:{$tanpaToken} sesi belum dibuatkan token";
            }
        }

        if (empty(trim((string) $ujian->tata_tertib))) {
            $masalah[] = 'TOKEN:Isi tata tertib ujian';
        }

        if ($ujian->kkm === null) {
            $masalah[] = 'TOKEN:Isi KKM untuk penentuan remedial';
        }

        return $masalah;
    }

    /**
     * Checklist khusus GURU untuk ujian resmi.
     * Guru hanya: buat paket soal + isi soal, lalu ajukan ke panitia.
     * Sesi, token, tata tertib, KKM = tugas panitia, jangan tampilkan ke guru.
     *
     * @return array<string> daftar masalah; kosong bila siap diajukan.
     */
    public function cekGuru(Ujian $ujian): array
    {
        $masalah = [];

        // Mode santai: cukup ada soal.
        if (! $ujian->kategoriBesar()) {
            $jumlahSoal = $ujian->memakaiPaketSoal()
                ? $ujian->paketSoals()->withCount('soals')->get()->sum('soals_count')
                : $ujian->soals()->whereNull('paket_soal_id')->count();

            if ($jumlahSoal === 0) {
                $masalah[] = 'Tambahkan minimal satu soal sebelum menerbitkan.';
            }

            return $masalah;
        }

        // Mode resmi: guru cukup siapkan paket + soal.
        if (! $ujian->memakaiPaketSoal()) {
            $masalah[] = 'Buat paket soal (minimal 2 paket, misal A dan B).';
        } else {
            $paketKosong = $ujian->paketSoals()->withCount('soals')->get()->filter(fn ($p) => $p->soals_count === 0);
            if ($paketKosong->isNotEmpty()) {
                $masalah[] = 'Paket '.$paketKosong->pluck('kode')->join(', ').' belum ada soalnya.';
            }
        }

        return $masalah;
    }

    public function lengkap(Ujian $ujian): bool
    {
        return $this->cek($ujian) === [];
    }

    public function lengkapGuru(Ujian $ujian): bool
    {
        return $this->cekGuru($ujian) === [];
    }
}
