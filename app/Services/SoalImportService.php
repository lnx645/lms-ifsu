<?php

namespace App\Services;

use App\Models\OpsiSoal;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

class SoalImportService
{
    public const HEADER = [
        'tipe', 'pertanyaan', 'poin', 'kode_kd', 'indikator',
        'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
        'kunci', 'kunci_case_sensitive',
    ];

    /**
     * Impor soal dari file XLSX ke ujian. Mengembalikan ringkasan hasil.
     *
     * @return array{berhasil: int, gagal: int, errors: array<int, string>}
     */
    public function impor(Ujian $ujian, string $path): array
    {
        $baris = $this->bacaBaris($path);
        $berhasil = 0;
        $errors = [];

        foreach ($baris as $index => $row) {
            $noBaris = $index + 2; // +1 header, +1 base-1

            try {
                $data = $this->validasiBaris($row, $noBaris);
                DB::transaction(function () use ($ujian, $data): void {
                    $this->simpanSoal($ujian, $data);
                });
                $berhasil++;
            } catch (ImportSoalException $e) {
                $errors[] = "Baris {$noBaris}: {$e->getMessage()}";
            }
        }

        return ['berhasil' => $berhasil, 'gagal' => count($errors), 'errors' => $errors];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function bacaBaris(string $path): array
    {
        $reader = new Reader(new Options);
        $reader->open($path);

        $baris = [];
        $headerOk = false;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(fn ($c): string => trim((string) $c), $row->toArray());

                if (! $headerOk) {
                    $headerOk = true;

                    continue;
                }

                if (count(array_filter($cells)) === 0) {
                    continue;
                }

                $baris[] = array_combine(
                    self::HEADER,
                    array_pad(array_slice($cells, 0, count(self::HEADER)), count(self::HEADER), '')
                );
            }
            break; // hanya sheet pertama
        }

        $reader->close();

        return $baris;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     *
     * @throws ImportSoalException
     */
    private function validasiBaris(array $row, int $noBaris): array
    {
        $tipe = strtolower($row['tipe']);

        if (! in_array($tipe, Soal::TIPE, true)) {
            throw new ImportSoalException("tipe '{$row['tipe']}' tidak valid (pg/multi/benar_salah/isian/esai)");
        }

        if ($row['pertanyaan'] === '') {
            throw new ImportSoalException('pertanyaan wajib diisi');
        }

        $poin = (int) $row['poin'];
        if ($poin < 1 || $poin > 100) {
            throw new ImportSoalException('poin harus 1-100');
        }

        $data = [
            'tipe' => $tipe,
            'pertanyaan' => $row['pertanyaan'],
            'poin' => $poin,
            'kode_kd' => $row['kode_kd'] !== '' ? $row['kode_kd'] : null,
            'indikator' => $row['indikator'] !== '' ? $row['indikator'] : null,
            'opsi' => [],
            'kunci_isian' => null,
            'isian_case_sensitive' => false,
        ];

        $opsiTeks = array_filter([
            'A' => $row['opsi_a'], 'B' => $row['opsi_b'], 'C' => $row['opsi_c'],
            'D' => $row['opsi_d'], 'E' => $row['opsi_e'],
        ], fn ($t): bool => $t !== '');

        if (in_array($tipe, ['pg', 'multi', 'benar_salah'], true)) {
            if (count($opsiTeks) < 2) {
                throw new ImportSoalException('minimal 2 opsi untuk tipe '.$tipe);
            }
            $data['opsi'] = $this->opsiDenganKunci($tipe, $opsiTeks, $row['kunci'], $noBaris);
        } elseif ($tipe === 'isian') {
            $kunci = array_filter(array_map('trim', explode('|', $row['kunci'])));
            if (empty($kunci)) {
                throw new ImportSoalException('kunci jawaban isian wajib diisi (pisahkan dengan |)');
            }
            $data['kunci_isian'] = array_values($kunci);
            $data['isian_case_sensitive'] = in_array(strtolower($row['kunci_case_sensitive']), ['1', 'ya', 'y', 'true'], true);
        }

        return $data;
    }

    /**
     * @param  array<string, string>  $opsiTeks
     * @return array<int, array{teks: string, benar: bool}>
     *
     * @throws ImportSoalException
     */
    private function opsiDenganKunci(string $tipe, array $opsiTeks, string $kunci, int $noBaris): array
    {
        $kunciHuruf = array_filter(array_map(fn ($k): string => strtoupper(trim($k)), explode(',', $kunci)));

        if (empty($kunciHuruf)) {
            throw new ImportSoalException('kunci jawaban wajib diisi (mis. A atau A,C)');
        }

        foreach ($kunciHuruf as $h) {
            if (! isset($opsiTeks[$h])) {
                throw new ImportSoalException("kunci '{$h}' tidak ada opsinya");
            }
        }

        if ($tipe === 'pg' && count($kunciHuruf) !== 1) {
            throw new ImportSoalException('pilihan ganda hanya boleh 1 kunci jawaban');
        }

        $hasil = [];
        foreach ($opsiTeks as $huruf => $teks) {
            $hasil[] = ['teks' => $teks, 'benar' => in_array($huruf, $kunciHuruf, true)];
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpanSoal(Ujian $ujian, array $data): void
    {
        $urutan = ($ujian->soals()->max('urutan') ?? 0) + 1;

        /** @var Soal $soal */
        $soal = $ujian->soals()->create([
            'tipe' => $data['tipe'],
            'pertanyaan' => $data['pertanyaan'],
            'poin' => $data['poin'],
            'urutan' => $urutan,
            'kode_kd' => $data['kode_kd'],
            'indikator' => $data['indikator'],
            'kunci_isian' => $data['kunci_isian'],
            'isian_case_sensitive' => $data['isian_case_sensitive'],
        ]);

        foreach ($data['opsi'] as $index => $opsi) {
            OpsiSoal::create([
                'soal_id' => $soal->id,
                'teks' => $opsi['teks'],
                'benar' => $opsi['benar'],
                'urutan' => $index + 1,
            ]);
        }
    }

    /**
     * Buat file template XLSX untuk diunduh guru.
     */
    public function buatTemplate(string $path): void
    {
        $writer = new Writer(new \OpenSpout\Writer\XLSX\Options);
        $writer->openToFile($path);

        $writer->addRow(Row::fromValues(self::HEADER));
        $writer->addRow(Row::fromValues([
            'pg', 'Ibukota Indonesia adalah...', '10', 'KD 3.1', 'Menyebutkan ibukota negara',
            'Jakarta', 'Bandung', 'Surabaya', 'Medan', '', 'A', '',
        ]));
        $writer->addRow(Row::fromValues([
            'isian', 'Hasil dari 7 x 8 adalah...', '10', '', '',
            '', '', '', '', '', '56', '',
        ]));

        $writer->close();
    }
}

class ImportSoalException extends \RuntimeException {}
