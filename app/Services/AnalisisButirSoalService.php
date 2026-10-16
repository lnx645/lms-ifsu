<?php

namespace App\Services;

use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Support\Collection;

class AnalisisButirSoalService
{
    /**
     * Analisis butir soal per ujian:
     * - taraf kesukaran (P): proporsi jawaban benar
     * - daya beda (D): selisih proporsi benar kelompok atas vs bawah (27%)
     * - efektivitas pengecoh: distribusi pilihan opsi
     * - reliabilitas tes: Cronbach's alpha
     *
     * @return array{soal: array<int, array<string, mixed>>, reliabilitas: ?float, jumlah_responden: int}
     */
    public function analisis(Ujian $ujian): array
    {
        $soals = $ujian->soals()->with('opsi')->orderBy('urutan')->get();

        // Nilai per pengerjaan per soal (hanya attempt selesai, bukan remedial ganda).
        $pengerjaans = $ujian->pengerjaans()
            ->whereIn('status', ['selesai', 'auto_submit'])
            ->with(['jawabans' => fn ($q) => $q->whereIn('soal_id', $soals->pluck('id'))])
            ->get();

        $jumlahResponden = $pengerjaans->count();

        if ($jumlahResponden === 0 || $soals->isEmpty()) {
            return ['soal' => [], 'reliabilitas' => null, 'jumlah_responden' => 0];
        }

        // Skor total per pengerjaan untuk pembagian kelompok atas/bawah.
        $skorTotal = $pengerjaans->mapWithKeys(fn ($p) => [
            $p->id => $p->jawabans->sum('skor'),
        ])->sortDesc();

        $nKelompok = (int) max(1, floor($jumlahResponden * 0.27));
        $kelompokAtas = $skorTotal->take($nKelompok)->keys();
        $kelompokBawah = $skorTotal->take(-$nKelompok)->keys();

        $hasilSoal = $soals->map(function (Soal $soal) use ($pengerjaans, $kelompokAtas, $kelompokBawah, $nKelompok): array {
            $jawabans = $pengerjaans->flatMap->jawabans->where('soal_id', $soal->id);
            $n = $jawabans->count();

            // Soal esai/isian tidak punya flag 'benar' — pakai rasio skor terhadap poin.
            $isEsai = in_array($soal->tipe, ['esai', 'isian'], true);
            $poin = (float) ($soal->poin ?: 1);

            if ($isEsai) {
                $rasioBenar = $jawabans->map(fn ($j) => min(1, max(0, (float) ($j->skor ?? 0) / $poin)));
                $kesukaran = $n > 0 ? $rasioBenar->avg() : null;
                $rataAtas = $jawabans->whereIn('ujian_pengerjaan_id', $kelompokAtas)->map(fn ($j) => min(1, max(0, (float) ($j->skor ?? 0) / $poin)))->avg();
                $rataBawah = $jawabans->whereIn('ujian_pengerjaan_id', $kelompokBawah)->map(fn ($j) => min(1, max(0, (float) ($j->skor ?? 0) / $poin)))->avg();
                $dayaBeda = ($rataAtas !== null && $rataBawah !== null) ? $rataAtas - $rataBawah : null;
            } else {
                $benar = $jawabans->where('benar', true)->count();
                $kesukaran = $n > 0 ? $benar / $n : null;

                $benarAtas = $jawabans->whereIn('ujian_pengerjaan_id', $kelompokAtas)->where('benar', true)->count();
                $benarBawah = $jawabans->whereIn('ujian_pengerjaan_id', $kelompokBawah)->where('benar', true)->count();
                $dayaBeda = $nKelompok > 0 ? ($benarAtas - $benarBawah) / $nKelompok : null;
            }

            return [
                'soal_id' => $soal->id,
                'urutan' => $soal->urutan,
                'tipe' => $soal->tipe,
                'kode_kd' => $soal->kode_kd,
                'pertanyaan' => mb_substr(strip_tags($soal->pertanyaan), 0, 120),
                'jumlah_responden' => $n,
                'taraf_kesukaran' => $kesukaran !== null ? round($kesukaran, 3) : null,
                'kategori_kesukaran' => $this->kategoriKesukaran($kesukaran),
                'daya_beda' => $dayaBeda !== null ? round($dayaBeda, 3) : null,
                'kategori_daya_beda' => $this->kategoriDayaBeda($dayaBeda),
                'pengecoh' => $this->efektivitasPengecoh($soal, $jawabans),
            ];
        })->values()->all();

        return [
            'soal' => $hasilSoal,
            'reliabilitas' => $this->cronbachAlpha($soals, $pengerjaans),
            'jumlah_responden' => $jumlahResponden,
        ];
    }

    private function kategoriKesukaran(?float $p): ?string
    {
        if ($p === null) {
            return null;
        }

        return match (true) {
            $p < 0.3 => 'sukar',
            $p <= 0.7 => 'sedang',
            default => 'mudah',
        };
    }

    private function kategoriDayaBeda(?float $d): ?string
    {
        if ($d === null) {
            return null;
        }

        return match (true) {
            $d >= 0.4 => 'baik',
            $d >= 0.2 => 'cukup',
            $d >= 0 => 'jelek',
            default => 'negatif',
        };
    }

    /**
     * Distribusi pilihan tiap opsi (untuk menilai pengecoh).
     *
     * @return array<int, array<string, mixed>>
     */
    private function efektivitasPengecoh(Soal $soal, Collection $jawabans): array
    {
        if (! in_array($soal->tipe, ['pg', 'multi', 'benar_salah'], true)) {
            return [];
        }

        $total = max(1, $jawabans->count());

        return $soal->opsi->map(function ($opsi) use ($jawabans, $total): array {
            $dipilih = $jawabans->filter(fn ($j): bool => in_array($opsi->id, array_map('intval', (array) ($j->opsi_dipilih ?? [])), true))->count();

            return [
                'opsi_id' => $opsi->id,
                'teks' => mb_substr(strip_tags($opsi->teks), 0, 60),
                'benar' => (bool) $opsi->benar,
                'dipilih' => $dipilih,
                'persentase' => round($dipilih / $total * 100, 1),
            ];
        })->values()->all();
    }

    /**
     * Cronbach's alpha dari matriks skor per soal.
     */
    private function cronbachAlpha(Collection $soals, Collection $pengerjaans): ?float
    {
        $k = $soals->count();

        if ($k < 2 || $pengerjaans->count() < 2) {
            return null;
        }

        $matriks = $pengerjaans->map(fn ($p) => $soals->map(
            fn ($s) => (float) ($p->jawabans->firstWhere('soal_id', $s->id)?->skor ?? 0)
        )->all())->all();

        $varButir = 0.0;
        for ($i = 0; $i < $k; $i++) {
            $kolom = array_column($matriks, $i);
            $varButir += $this->varians($kolom);
        }

        $skorTotal = array_map('array_sum', $matriks);
        $varTotal = $this->varians($skorTotal);

        if ($varTotal <= 0) {
            return null;
        }

        return round($k / ($k - 1) * (1 - $varButir / $varTotal), 3);
    }

    /**
     * @param  array<int, float>  $xs
     */
    private function varians(array $xs): float
    {
        $n = count($xs);

        if ($n < 2) {
            return 0.0;
        }

        $mean = array_sum($xs) / $n;
        $ss = array_sum(array_map(fn ($x): float => ($x - $mean) ** 2, $xs));

        return $ss / ($n - 1);
    }
}
