<?php

namespace App\Services;

use App\Models\DetailPenilaian;
use App\Models\JawabanSiswa;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use Illuminate\Support\Facades\DB;

class UjianService
{
    /**
     * Buat token acak 6 karakter (huruf/angka kapital tanpa karakter ambigu).
     */
    public function generateToken(): string
    {
        $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa O/0/I/1
        $token = '';

        for ($i = 0; $i < 6; $i++) {
            $token .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return $token;
    }

    /**
     * Nilai satu jawaban objektif. Mengembalikan true jika benar.
     * Mendukung 16 tipe bank soal + tipe legacy.
     */
    public function nilaiJawabanObjektif(Soal $soal, JawabanSiswa $jawaban): bool
    {
        // Decode jawaban dari jawaban_teks (JSON) atau opsi_dipilih (legacy)
        $jw = $this->decodeJawaban($jawaban);
        $detail = $soal->detail ?? [];
        $tipe = $soal->tipe;

        return match ($tipe) {
            'benar_salah' => $this->nilaiBenarSalahBaru($jw, $detail, $soal),

            // === 16 tipe baru ===
            'pilihan_ganda', 'pilihan_ganda_citra' => $this->nilaiPilihanGandaBaru($jw, $detail),
            'pilihan_ganda_kompleks' => $this->nilaiPilihanGandaKompleksBaru($jw, $detail),
            'menjodohkan', 'menjodohkan_banyak' => $this->nilaiMenjodohkanBaru($jw, $detail),
            'isian_singkat' => $this->nilaiIsianSingkatBaru($jw, $detail),
            'mengurutkan' => $this->nilaiMengurutkanBaru($jw, $detail),
            'setuju_tidak' => $this->nilaiPernyataanBaru($jw, $detail),
            'matrix_text', 'matrix_checkbox' => $this->nilaiMatrixBaru($jw, $detail),
            'mengelompokkan' => $this->nilaiMengelompokkanBaru($jw, $detail),
            'blank_space' => $this->nilaiBlankSpaceBaru($jw, $detail),
            'image_labeling' => $this->nilaiImageLabelingBaru($jw, $detail),
            'listening' => $this->nilaiListeningBaru($jw, $detail),
            // uraian/esai: manual
            default => false,
        };
    }

    /**
     * Konversi aman ke string (cegah Array to string conversion).
     */
    private function str(mixed $v): string
    {
        if (is_array($v)) {
            $v = $v[0] ?? '';

            return $this->str($v);
        }
        if (is_object($v)) {
            return '';
        }

        return trim((string) $v);
    }

    /**
     * Decode jawaban dari jawaban_teks (JSON/string) atau opsi_dipilih.
     */
    private function decodeJawaban(JawabanSiswa $jawaban): mixed
    {
        $t = $jawaban->jawaban_teks;
        if ($t !== null && $t !== '') {
            $trimmed = trim($t);
            if (($trimmed[0] ?? '') === '{' || ($trimmed[0] ?? '') === '[') {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            return $trimmed;
        }

        return $jawaban->opsi_dipilih;
    }

    /** Pilihan ganda: jawaban 'a'/'b'/... cocok dengan kunci. */
    private function nilaiPilihanGandaBaru(mixed $jw, array $detail): bool
    {
        $kunci = $detail['kunci'] ?? null;
        if ($kunci === null || $kunci === '') {
            return false;
        }
        $j = is_array($jw) ? ($jw[0] ?? '') : (string) $jw;

        return strtolower($this->str($j)) === strtolower($this->str($kunci));
    }

    /** PG Kompleks: set jawaban harus sama persis dengan set kunci. */
    private function nilaiPilihanGandaKompleksBaru(mixed $jw, array $detail): bool
    {
        $kunci = $detail['kunci'] ?? [];
        if (! is_array($kunci)) {
            $kunci = [$kunci];
        }
        $j = is_array($jw) ? $jw : [$jw];
        $jk = array_map(fn ($x) => strtolower($this->str($x)), $j);
        $kk = array_map(fn ($x) => strtolower($this->str($x)), $kunci);
        sort($jk);
        sort($kk);

        return ! empty($kk) && $jk === $kk;
    }

    /** Menjodohkan: semua pasangan harus cocok. */
    private function nilaiMenjodohkanBaru(mixed $jw, array $detail): bool
    {
        $pasangan = $detail['pasangan'] ?? [];
        if (empty($pasangan) || ! is_array($jw)) {
            return false;
        }
        foreach ($pasangan as $i => $p) {
            $kunciKanan = $p['kanan'] ?? $p['pasangan'] ?? null;
            $jawabKanan = $jw[$i] ?? $jw[(string) $i] ?? null;
            if (strtolower($this->str($jawabKanan)) !== strtolower($this->str($kunciKanan))) {
                return false;
            }
        }

        return true;
    }

    /** Isian singkat: cocok dengan salah satu kunci (case-insensitive). */
    private function nilaiIsianSingkatBaru(mixed $jw, array $detail): bool
    {
        $kunci = $detail['kunci'] ?? [];
        if (! is_array($kunci)) {
            $kunci = [$kunci];
        }
        $j = strtolower($this->str(is_array($jw) ? ($jw[0] ?? '') : $jw));
        if ($j === '') {
            return false;
        }
        foreach ($kunci as $k) {
            if ($j === strtolower($this->str($k))) {
                return true;
            }
        }

        return false;
    }

    /** Mengurutkan: urutan harus persis sama. */
    private function nilaiMengurutkanBaru(mixed $jw, array $detail): bool
    {
        $kunci = $detail['kunci'] ?? $detail['urutan_benar'] ?? $detail['urutan'] ?? [];
        if (! is_array($jw) || empty($kunci)) {
            return false;
        }

        return array_values($jw) === array_values($kunci);
    }

    /** Benar/Salah & Setuju/Tidak: per pernyataan harus cocok. */
    private function nilaiPernyataanBaru(mixed $jw, array $detail): bool
    {
        return $this->nilaiBenarSalahBaru($jw, $detail, null);
    }

    private function nilaiBenarSalahBaru(mixed $jw, array $detail, ?Soal $soal): bool
    {
        $pernyataan = $detail['pernyataan'] ?? [];
        // Fallback legacy: dari opsi
        if (empty($pernyataan) && $soal) {
            $pernyataan = $soal->opsi->map(fn ($o) => [
                'teks' => $o->teks,
                'jawaban' => $o->benar ? 'Benar' : 'Salah',
            ])->toArray();
        }
        if (empty($pernyataan) || ! is_array($jw)) {
            return false;
        }
        foreach ($pernyataan as $i => $p) {
            $kunci = strtolower($this->str($p['jawaban'] ?? ''));
            $jawab = strtolower($this->str($jw[$i] ?? $jw[(string) $i] ?? ''));
            // Normalisasi: benar/true/b, salah/false/s
            $norm = fn ($v) => in_array($v, ['benar', 'true', 'b', 'setuju']) ? 'benar' : (in_array($v, ['salah', 'false', 's', 'tidak']) ? 'salah' : $v);
            if ($norm($jawab) !== $norm($kunci)) {
                return false;
            }
        }

        return true;
    }

    /** Matrix: semua sel harus cocok. */
    private function nilaiMatrixBaru(mixed $jw, array $detail): bool
    {
        $kunci = $detail['kunci'] ?? $detail['jawaban'] ?? [];
        if (empty($kunci) || ! is_array($jw)) {
            return false;
        }
        foreach ($kunci as $k => $v) {
            $jv = $jw[$k] ?? $jw[(string) $k] ?? null;
            if (is_array($v)) {
                if (array_values((array) $jv) !== array_values($v)) {
                    return false;
                }
            } elseif (strtolower($this->str($jv)) !== strtolower($this->str($v))) {
                return false;
            }
        }

        return true;
    }

    /** Mengelompokkan: setiap item harus di kelompok yang benar. */
    private function nilaiMengelompokkanBaru(mixed $jw, array $detail): bool
    {
        $kunci = $detail['kunci'] ?? $detail['kelompok'] ?? [];
        if (empty($kunci) || ! is_array($jw)) {
            return false;
        }
        // Normalisasi: [{nama,items}] -> {nama: items}
        if (isset($kunci[0]) && is_array($kunci[0]) && isset($kunci[0]['nama'])) {
            $norm = [];
            foreach ($kunci as $k) {
                $norm[$k['nama']] = $k['items'] ?? [];
            }
            $kunci = $norm;
        }
        foreach ($kunci as $kelompok => $items) {
            $jItems = $jw[$kelompok] ?? $jw[(string) $kelompok] ?? [];
            $jk = array_map(fn ($x) => $this->str($x), (array) $jItems);
            $kk = array_map(fn ($x) => $this->str($x), (array) $items);
            sort($jk);
            sort($kk);
            if ($jk !== $kk) {
                return false;
            }
        }

        return true;
    }

    /** Blank space: setiap blank harus cocok. */
    private function nilaiBlankSpaceBaru(mixed $jw, array $detail): bool
    {
        $kunci = $detail['kunci'] ?? [];
        if (! is_array($kunci)) {
            $kunci = [$kunci];
        }
        $j = is_array($jw) ? $jw : [$jw];
        if (count($j) !== count($kunci)) {
            return false;
        }
        foreach ($kunci as $i => $k) {
            $kk = is_array($k) ? $k : [$k];
            $jv = strtolower($this->str($j[$i] ?? ''));
            $cocok = false;
            foreach ($kk as $alt) {
                if ($jv === strtolower($this->str($alt))) {
                    $cocok = true;
                    break;
                }
            }
            if (! $cocok) {
                return false;
            }
        }

        return true;
    }

    /** Image labeling: semua label harus cocok. */
    private function nilaiImageLabelingBaru(mixed $jw, array $detail): bool
    {
        $markers = $detail['markers'] ?? [];
        if (empty($markers) || ! is_array($jw)) {
            return false;
        }
        foreach ($markers as $i => $m) {
            $kunci = strtolower($this->str($m['label'] ?? $m['kunci'] ?? ''));
            $jawab = strtolower($this->str($jw[$i] ?? $jw[(string) $i] ?? ''));
            if ($jawab !== $kunci) {
                return false;
            }
        }

        return true;
    }

    /** Listening: seperti pilihan ganda. */
    private function nilaiListeningBaru(mixed $jw, array $detail): bool
    {
        return $this->nilaiPilihanGandaBaru($jw, $detail);
    }

    /**
     * Nilai ulang semua jawaban objektif pada sebuah sesi pengerjaan.
     */
    public function gradeObjektif(UjianPengerjaan $pengerjaan): void
    {
        $pengerjaan->loadMissing(['jawabans.soal.opsi']);

        foreach ($pengerjaan->jawabans as $jawaban) {
            $soal = $jawaban->soal;

            if ($soal === null || ! $soal->objektif()) {
                continue;
            }

            $benar = $this->nilaiJawabanObjektif($soal, $jawaban);
            $jawaban->benar = $benar;
            $jawaban->skor = $benar ? $soal->poin : 0;
            $jawaban->save();
        }
    }

    /**
     * Hitung ulang nilai_objektif, nilai_esai, nilai_total dan sinkron ke penilaian
     * jika seluruh soal esai telah dinilai. Mengembalikan true jika sudah lengkap.
     */
    public function rekalkulasiNilai(UjianPengerjaan $pengerjaan): bool
    {
        $ujian = $pengerjaan->ujian;
        $ujian->loadMissing('soals');
        $pengerjaan->loadMissing(['jawabans.soal']);

        $totalPoin = max(1, (int) $ujian->soals->sum('poin'));

        $poinObjektif = 0.0;
        $poinEsai = 0.0;
        $esaiPending = false;

        // Soal esai yang ada pada ujian
        $soalEsaiIds = $ujian->soals->filter(fn ($s) => $s->esai())->pluck('id');
        $jawabanBySoal = $pengerjaan->jawabans->keyBy('soal_id');

        foreach ($pengerjaan->jawabans as $jawaban) {
            $soal = $jawaban->soal;

            if ($soal === null) {
                continue;
            }

            if ($soal->objektif()) {
                $poinObjektif += (float) ($jawaban->skor ?? 0);
            } elseif ($soal->esai()) {
                if ($jawaban->skor === null) {
                    $esaiPending = true;
                } else {
                    $poinEsai += (float) $jawaban->skor;
                }
            }
        }

        // Soal esai yang tidak dijawab sama sekali dianggap 0 (bukan pending),
        // agar nilai tidak macet selamanya. Hanya jawaban esai yang ADA tapi
        // belum diberi skor yang dianggap pending.
        foreach ($soalEsaiIds as $soalId) {
            $jawaban = $jawabanBySoal->get($soalId);

            if ($jawaban === null) {
                continue;
            }

            if ($jawaban->skor === null) {
                $esaiPending = true;
            }
        }

        $pengerjaan->nilai_objektif = round($poinObjektif / $totalPoin * $ujian->nilai_maks, 2);
        $pengerjaan->nilai_esai = $esaiPending ? null : round($poinEsai / $totalPoin * $ujian->nilai_maks, 2);

        if ($esaiPending) {
            $pengerjaan->nilai_total = null;
            $pengerjaan->save();

            return false;
        }

        $pengerjaan->nilai_total = round(($poinObjektif + $poinEsai) / $totalPoin * $ujian->nilai_maks, 2);
        $pengerjaan->save();

        $this->syncPenilaian($pengerjaan);

        return true;
    }

    /**
     * Sinkronkan nilai_total ke detail_penilaian (pola TugasController::nilai()).
     */
    public function syncPenilaian(UjianPengerjaan $pengerjaan): void
    {
        $ujian = $pengerjaan->ujian;

        if ($ujian->penilaian_id === null || $pengerjaan->nilai_total === null) {
            return;
        }

        DetailPenilaian::updateOrCreate(
            [
                'penilaian_id' => $ujian->penilaian_id,
                'guru_kelas_id' => $ujian->guru_kelas_id,
                'siswa_nisn' => $pengerjaan->siswa_nisn,
            ],
            [
                'tahun_ajaran_id' => $ujian->guruKelas->tahun_ajaran_id,
                'guru_id' => $ujian->guru_id,
                'nilai' => $pengerjaan->nilai_total,
                'sumber' => $ujian->kategori,
            ],
        );
    }

    /**
     * Default proctoring/atribut per kategori (dipakai saat membuat ujian).
     *
     * @return array<string, int|bool>
     */
    public function defaultKategori(string $kategori): array
    {
        return match ($kategori) {
            'usbk' => [
                'maks_attempt' => 1,
                'acak_soal' => true,
                'acak_opsi' => true,
                'wajib_fullscreen' => true,
                'maks_pelanggaran' => 3,
                'bobot' => 3,
            ],
            'uas' => [
                'maks_attempt' => 1,
                'acak_soal' => true,
                'acak_opsi' => true,
                'wajib_fullscreen' => true,
                'maks_pelanggaran' => 5,
                'bobot' => 3,
            ],
            'uts' => [
                'maks_attempt' => 1,
                'acak_soal' => true,
                'acak_opsi' => true,
                'wajib_fullscreen' => true,
                'maks_pelanggaran' => 5,
                'bobot' => 2,
            ],
            default => [ // kuis & uh
                'maks_attempt' => 1,
                'acak_soal' => false,
                'acak_opsi' => false,
                'wajib_fullscreen' => false,
                'maks_pelanggaran' => 0,
                'bobot' => 1,
            ],
        };
    }

    /**
     * Duplikat ujian beserta soal, opsi, dan sesi (tanpa peserta & pengerjaan).
     * Hasil selalu berstatus draft agar guru meninjau ulang sebelum terbit.
     */
    public function duplikat(Ujian $ujian, ?int $guruKelasId = null): Ujian
    {
        return DB::transaction(function () use ($ujian, $guruKelasId): Ujian {
            $baru = $ujian->replicate([
                'token', 'token_released_at', 'token_pengelola',
            ]);
            $baru->judul = $ujian->judul.' (Salinan)';
            $baru->status = 'draft';
            $baru->token = null;
            $baru->token_released_at = null;

            if ($guruKelasId !== null) {
                $baru->guru_kelas_id = $guruKelasId;
            }

            $baru->save();

            $ujian->load('soals.opsi');

            foreach ($ujian->soals as $soal) {
                $soalBaru = $soal->replicate();
                $soalBaru->ujian_id = $baru->id;
                $soalBaru->save();

                foreach ($soal->opsi as $opsi) {
                    $opsiBaru = $opsi->replicate();
                    $opsiBaru->soal_id = $soalBaru->id;
                    $opsiBaru->save();
                }
            }

            foreach ($ujian->sesis()->with('pengawas')->get() as $sesi) {
                $sesiBaru = $sesi->replicate(['ruang_ujian_id']);
                $sesiBaru->ujian_id = $baru->id;
                $sesiBaru->ruang_ujian_id = null;
                $sesiBaru->save();

                foreach ($sesi->pengawas as $pw) {
                    $pwBaru = $pw->replicate();
                    $pwBaru->sesi_ujian_id = $sesiBaru->id;
                    $pwBaru->save();
                }
            }

            return $baru->fresh();
        });
    }
}
