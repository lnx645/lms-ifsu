<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

class SiswaImportService
{
    public const HEADER = [
        'nisn', 'nama_lengkap', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
    ];

    /**
     * Impor siswa dari file XLSX. Membuat record siswa + akun user.
     * Password default: nisn (siswa wajib ganti saat pertama login).
     *
     * @return array{berhasil: int, gagal: int, errors: array<int, string>}
     */
    public function impor(string $path): array
    {
        $baris = $this->bacaBaris($path);
        $berhasil = 0;
        $errors = [];

        foreach ($baris as $index => $row) {
            $noBaris = $index + 2;

            try {
                $data = $this->validasiBaris($row, $noBaris);
                DB::transaction(function () use ($data): void {
                    $this->simpanSiswa($data);
                });
                $berhasil++;
            } catch (ImportSiswaException $e) {
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
            break;
        }

        $reader->close();

        return $baris;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private function validasiBaris(array $row, int $noBaris): array
    {
        $nisn = $row['nisn'];
        $nama = $row['nama_lengkap'];

        if ($nisn === '' || $nama === '') {
            throw new ImportSiswaException('NISN dan nama lengkap wajib diisi.');
        }

        if (! preg_match('/^\d{10}$/', $nisn)) {
            throw new ImportSiswaException("NISN '{$nisn}' harus 10 digit angka.");
        }

        if (Siswa::where('nisn', $nisn)->exists()) {
            throw new ImportSiswaException("NISN '{$nisn}' sudah terdaftar — dilewati.");
        }

        $jk = strtolower($row['jenis_kelamin']);
        if ($jk !== '' && ! in_array($jk, ['l', 'p', 'laki-laki', 'perempuan'], true)) {
            throw new ImportSiswaException("Jenis kelamin '{$row['jenis_kelamin']}' tidak dikenal (isi L/P).");
        }

        return [
            'nisn' => $nisn,
            'nama_lengkap' => $nama,
            'jenis_kelamin' => in_array($jk, ['l', 'laki-laki'], true) ? 'Laki-laki' : ($jk !== '' ? 'Perempuan' : null),
            'tempat_lahir' => $row['tempat_lahir'] ?: null,
            'tanggal_lahir' => $row['tanggal_lahir'] ?: null,
        ];
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function simpanSiswa(array $data): void
    {
        Siswa::create($data);

        User::create([
            'name' => $data['nama_lengkap'],
            'email' => $data['nisn'].'@smkifsu.sch.id',
            'password' => Hash::make($data['nisn']),
            'role' => 'siswa',
        ]);
    }
}

class ImportSiswaException extends \RuntimeException {}
