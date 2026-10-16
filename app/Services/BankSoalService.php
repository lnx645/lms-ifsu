<?php

namespace App\Services;

use App\Models\BankSoal;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Support\Facades\DB;

class BankSoalService
{
    /**
     * Salin sejumlah soal bank (snapshot) ke sebuah ujian.
     * Mengembalikan jumlah soal yang berhasil disalin.
     *
     * @param  array<int, int>  $bankSoalIds
     */
    public function salinKeUjian(Ujian $ujian, array $bankSoalIds): int
    {
        $bankSoals = BankSoal::with('opsi')
            ->whereIn('id', $bankSoalIds)
            ->get();

        if ($bankSoals->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($ujian, $bankSoals): int {
            $urutan = (int) $ujian->soals()->max('urutan');
            $jumlah = 0;

            foreach ($bankSoals as $bank) {
                $urutan++;

                $soal = $ujian->soals()->create([
                    'bank_soal_id' => $bank->id,
                    'tipe' => $bank->tipe,
                    'pertanyaan' => $bank->pertanyaan,
                    'poin' => $bank->poin,
                    'urutan' => $urutan,
                    'kunci_isian' => $bank->kunci_isian,
                    'isian_case_sensitive' => $bank->isian_case_sensitive,
                    'detail' => $bank->detail,
                ]);

                foreach ($bank->opsi as $opsi) {
                    $soal->opsi()->create([
                        'teks' => $opsi->teks,
                        'benar' => $opsi->benar,
                        'urutan' => $opsi->urutan,
                    ]);
                }

                $jumlah++;
            }

            return $jumlah;
        });
    }

    /**
     * Simpan soal ujian ke bank (snapshot balik) untuk matpel tertentu.
     *
     * @param  array{topik?: ?string, kesulitan?: string}  $meta
     */
    public function simpanDariSoal(Soal $soal, int $matpelId, ?int $guruId, array $meta = []): BankSoal
    {
        $soal->loadMissing('opsi');

        return DB::transaction(function () use ($soal, $matpelId, $guruId, $meta): BankSoal {
            $bank = BankSoal::create([
                'matpel_id' => $matpelId,
                'guru_id' => $guruId,
                'tipe' => $soal->tipe,
                'pertanyaan' => $soal->pertanyaan,
                'poin' => $soal->poin,
                'topik' => $meta['topik'] ?? null,
                'kesulitan' => $meta['kesulitan'] ?? 'sedang',
                'kunci_isian' => $soal->kunci_isian,
                'isian_case_sensitive' => $soal->isian_case_sensitive,
            ]);

            foreach ($soal->opsi as $opsi) {
                $bank->opsi()->create([
                    'teks' => $opsi->teks,
                    'benar' => $opsi->benar,
                    'urutan' => $opsi->urutan,
                ]);
            }

            return $bank;
        });
    }

    /**
     * Ambil N soal acak dari bank sesuai kriteria lalu salin ke ujian.
     *
     * @param  array{matpel_id: int, jumlah: int, tipe?: ?string, topik?: ?string, kesulitan?: ?string}  $kriteria
     */
    public function generate(Ujian $ujian, array $kriteria): int
    {
        $ids = BankSoal::query()
            ->where('matpel_id', $kriteria['matpel_id'])
            ->when($kriteria['guru_id'] ?? null, fn ($q, $g) => $q->where('guru_id', $g))
            ->when($kriteria['tipe'] ?? null, fn ($q, $t) => $q->where('tipe', $t))
            ->when($kriteria['topik'] ?? null, fn ($q, $t) => $q->where('topik', $t))
            ->when($kriteria['kesulitan'] ?? null, fn ($q, $k) => $q->where('kesulitan', $k))
            ->inRandomOrder()
            ->limit(max(1, (int) $kriteria['jumlah']))
            ->pluck('id')
            ->all();

        return $this->salinKeUjian($ujian, $ids);
    }
}
