<?php

namespace App\Services;

use App\Models\BankSoal;
use App\Models\PaketSoal;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use Illuminate\Support\Facades\DB;

/**
 * CBT v2: pengelolaan paket soal (varian A/B/C) untuk ujian resmi.
 *
 * - generateOtomatis(): bagi soal dari bank ke N paket secara acak (1 klik).
 * - tentukanPaketUntukSiswa(): distribusi round-robin per sesi agar siswa
 *   yang berdekatan mendapat paket berbeda.
 */
class PaketSoalService
{
    /**
     * Generate N paket soal otomatis dari bank soal.
     *
     * @param  array<int>  $bankSoalIds
     * @return array<PaketSoal>
     *
     * @throws \InvalidArgumentException
     */
    public function generateOtomatis(Ujian $ujian, int $jumlahPaket, int $soalPerPaket, array $bankSoalIds): array
    {
        if ($jumlahPaket < 1 || $jumlahPaket > 10) {
            throw new \InvalidArgumentException('Jumlah paket harus antara 1 dan 10.');
        }

        if ($soalPerPaket < 1) {
            throw new \InvalidArgumentException('Soal per paket minimal 1.');
        }

        $bankSoals = BankSoal::with('opsi')->whereIn('id', $bankSoalIds)->get();

        if ($bankSoals->count() < $jumlahPaket * $soalPerPaket) {
            throw new \InvalidArgumentException(
                "Bank soal kurang: butuh {$jumlahPaket} paket × {$soalPerPaket} soal = ".
                ($jumlahPaket * $soalPerPaket)." soal, tersedia {$bankSoals->count()}."
            );
        }

        return DB::transaction(function () use ($ujian, $jumlahPaket, $soalPerPaket, $bankSoals) {
            // Hapus paket lama (beserta soalnya via cascade) agar generate ulang bersih.
            $ujian->paketSoals()->delete();

            $acak = $bankSoals->shuffle();
            $pakets = [];

            for ($i = 0; $i < $jumlahPaket; $i++) {
                $kode = chr(ord('A') + $i);
                $paket = $ujian->paketSoals()->create([
                    'kode' => $kode,
                    'nama' => "Paket {$kode}",
                ]);

                $untukPaket = $acak->slice($i * $soalPerPaket, $soalPerPaket);
                $urutan = 0;
                foreach ($untukPaket as $bank) {
                    $urutan++;
                    $soal = $paket->soals()->create([
                        'ujian_id' => $ujian->id,
                        'bank_soal_id' => $bank->id,
                        'tipe' => $bank->tipe,
                        'pertanyaan' => $bank->pertanyaan,
                        'poin' => $bank->poin,
                        'urutan' => $urutan,
                        'kunci_isian' => $bank->kunci_isian,
                        'isian_case_sensitive' => $bank->isian_case_sensitive,
                        'kode_kd' => $bank->kode_kd,
                        'indikator' => $bank->indikator,
                    ]);

                    foreach ($bank->opsi as $opsi) {
                        $soal->opsi()->create([
                            'teks' => $opsi->teks,
                            'benar' => $opsi->benar,
                            'urutan' => $opsi->urutan,
                        ]);
                    }
                }

                $pakets[] = $paket;
            }

            return $pakets;
        });
    }

    /**
     * Tentukan paket untuk siswa dengan distribusi round-robin per sesi.
     *
     * Siswa ke-N dalam sesi mendapat paket ke-(N mod jumlah paket),
     * sehingga siswa yang mulai berurutan mendapat paket berbeda.
     */
    public function tentukanPaketUntukSiswa(Ujian $ujian, ?int $sesiId): ?PaketSoal
    {
        $pakets = $ujian->paketSoals()->orderBy('kode')->get();

        if ($pakets->isEmpty()) {
            return null;
        }

        if ($pakets->count() === 1) {
            return $pakets->first();
        }

        // Hitung berapa pengerjaan sudah dibuat di sesi ini (atau ujian ini bila tanpa sesi).
        $query = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->whereNotNull('paket_soal_id');

        if ($sesiId !== null) {
            $query->where('sesi_ujian_id', $sesiId);
        }

        $sudahAda = $query->count();
        $index = $sudahAda % $pakets->count();

        return $pakets[$index];
    }
}
