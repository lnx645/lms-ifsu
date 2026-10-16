<?php

namespace App\Services;

use App\Models\BankSoal;
use App\Models\KelompokBankSoal;
use App\Models\Ujian;

class PoolSoalService
{
    /**
     * Pilih soal acak dari pool (untuk generate saat guru simpan).
     *
     * @param  array<array{kelompok_bank_soal_id: int, jumlah: int}>  $pool
     * @return array<int> Daftar bank_soal_id terpilih
     */
    public function pilihAcak(array $pool): array
    {
        $terpilih = [];

        foreach ($pool as $item) {
            $kelompokId = $item['kelompok_bank_soal_id'] ?? null;
            $jumlah = (int) ($item['jumlah'] ?? 0);

            if (! $kelompokId || $jumlah < 1) {
                continue;
            }

            // Ambil komposisi kelompok jika ada
            $kelompok = KelompokBankSoal::find($kelompokId);
            $komposisi = $kelompok?->komposisi ?? [];
            // Jika semua persentase 0, anggap tidak ada komposisi
            $adaKomposisi = collect($komposisi)->contains(fn($c) => ((float)($c["persentase"] ?? 0)) > 0);

            if ($adaKomposisi) {
                // Distribusikan jumlah berdasarkan persentase komposisi
                foreach ($komposisi as $tipe => $cfg) {
                    $persen = (float) ($cfg['persentase'] ?? 0);
                    $maxSoal = (int) ($cfg['max_soal'] ?? 0);
                    if ($persen <= 0) {
                        continue;
                    }

                    $butuh = (int) round($jumlah * $persen / 100);
                    if ($maxSoal > 0) {
                        $butuh = min($butuh, $maxSoal);
                    }
                    if ($butuh < 1) {
                        continue;
                    }

                    $ids = BankSoal::where('kelompok_bank_soal_id', $kelompokId)
                        ->where('tipe', $tipe)
                        ->inRandomOrder()
                        ->limit($butuh)
                        ->pluck('id')
                        ->all();

                    $terpilih = array_merge($terpilih, $ids);
                }
            } else {
                // Tanpa komposisi: acak biasa
                $ids = BankSoal::where('kelompok_bank_soal_id', $kelompokId)
                    ->inRandomOrder()
                    ->limit($jumlah)
                    ->pluck('id')
                    ->all();

                $terpilih = array_merge($terpilih, $ids);
            }
        }

        return array_values(array_unique($terpilih));
    }

    /**
     * Pilih soal acak dari pool untuk siswa tertentu.
     * Deterministik per NISN + ujian ID (refresh tetap sama).
     *
     * @return array<int> Daftar bank_soal_id terpilih
     */
    public function pilihUntukSiswa(Ujian $ujian, string $nisn): array
    {
        $pool = $ujian->pool_soal ?? [];
        if (empty($pool)) {
            return [];
        }

        $terpilih = [];

        foreach ($pool as $item) {
            $kelompokId = $item['kelompok_bank_soal_id'] ?? null;
            $jumlah = (int) ($item['jumlah'] ?? 0);

            if (! $kelompokId || $jumlah < 1) {
                continue;
            }

            $ids = BankSoal::where('kelompok_bank_soal_id', $kelompokId)
                ->pluck('id')
                ->all();

            if (empty($ids)) {
                continue;
            }

            // Deterministik: seed dari nisn + ujian + kelompok
            $seed = crc32($nisn.'|'.$ujian->id.'|'.$kelompokId);
            mt_srand($seed);

            // Fisher-Yates shuffle dengan seed
            $shuffled = $ids;
            $n = count($shuffled);
            for ($i = $n - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                [$shuffled[$i], $shuffled[$j]] = [$shuffled[$j], $shuffled[$i]];
            }

            // Ambil sesuai jumlah (atau semua jika kurang)
            $ambil = min($jumlah, count($shuffled));
            $terpilih = array_merge($terpilih, array_slice($shuffled, 0, $ambil));
        }

        return array_values(array_unique($terpilih));
    }
}
