<?php

namespace App\Services;

use App\Models\DetailPenilaian;
use App\Models\GuruKelas;
use App\Models\SiswaKelas;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Export daftar nilai per kelas per matpel ke .xlsx
 * dengan gaya laporan pendidikan Indonesia.
 */
class ExportNilaiService
{
    /**
     * Bangun file xlsx dan kembalikan path file sementara.
     */
    public function export(GuruKelas $guruKelas): string
    {
        $data = $this->kumpulkan($guruKelas);

        $path = tempnam(sys_get_temp_dir(), 'nilai-').'.xlsx';

        $writer = new Writer(new Options);
        $writer->openToFile($path);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Daftar Nilai');

        $judul = (new Style)->withFontBold(true)->withFontSize(14);
        $subJudul = (new Style)->withFontBold(true)->withFontSize(12);
        $label = (new Style)->withFontBold(true)->withFontSize(11);
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontSize(11)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor(Color::toARGB(Color::rgb(31, 97, 141)));
        $altStyle = (new Style)->withBackgroundColor(Color::toARGB(Color::rgb(240, 248, 255)));
        $rataStyle = (new Style)->withFontBold(true)->withBackgroundColor(Color::toARGB(Color::rgb(255, 243, 205)));
        $ttd = (new Style)->withFontSize(11);

        // Kop
        $writer->addRow(Row::fromValuesWithStyle(['SMK INFORMATIKA SUMEDANG'], $judul));
        $writer->addRow(Row::fromValuesWithStyle(['DAFTAR NILAI SISWA'], $subJudul));
        $writer->addRow(Row::fromValues(['']));

        // Info
        $writer->addRow(Row::fromValuesWithStyle(['Kelas', ': '.$data['kelas']], $label));
        $writer->addRow(Row::fromValuesWithStyle(['Mata Pelajaran', ': '.$data['matpel']], $label));
        $writer->addRow(Row::fromValuesWithStyle(['Guru', ': '.$data['guru']], $label));
        $writer->addRow(Row::fromValuesWithStyle(['Tahun Ajaran', ': '.$data['tahun_ajaran']], $label));
        $writer->addRow(Row::fromValues(['']));

        // Header tabel
        $headers = ['No', 'NIS', 'Nama Siswa'];
        foreach ($data['kolom'] as $k) {
            $headers[] = $k['nama'];
        }
        $headers[] = 'Rata-rata';
        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        // Baris siswa
        foreach ($data['siswas'] as $i => $s) {
            $row = [$i + 1, $s['nis'] ?? '-', $s['nama']];
            foreach ($s['nilai'] as $n) {
                $row[] = $n === null ? '-' : $n;
            }
            $row[] = $s['rata_rata'] === null ? '-' : $s['rata_rata'];
            $style = $i % 2 === 1 ? $altStyle : new Style;
            $writer->addRow(Row::fromValuesWithStyle($row, $style));
        }

        // Rata-rata kelas
        $writer->addRow(Row::fromValuesWithStyle(
            array_merge(['', '', 'Rata-rata kelas'], $data['rata_kolom'], [$data['rata_umum'] ?? '-']),
            $rataStyle
        ));

        // Tanda tangan
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValuesWithStyle(
            ['', '', '', 'Sumedang, '.Carbon::now()->translatedFormat('d F Y')],
            $ttd
        ));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValuesWithStyle(['Mengetahui,', '', '', 'Guru Mata Pelajaran,'], $ttd));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValuesWithStyle(
            ['( ............................................ )', '', '', '( '.$data['guru'].' )'],
            $ttd
        ));
        if ($data['nip']) {
            $writer->addRow(Row::fromValuesWithStyle(['', '', '', 'NIP. '.$data['nip']], $ttd));
        }

        // Lebar kolom
        $sheet->setColumnWidth(5, 1);
        $sheet->setColumnWidth(14, 2);
        $sheet->setColumnWidth(30, 3);
        $col = 4;
        foreach ($data['kolom'] as $k) {
            $sheet->setColumnWidth(max(14, mb_strlen($k['nama']) + 2), $col++);
        }
        $sheet->setColumnWidth(12, $col);

        $writer->close();

        return $path;
    }

    /**
     * Kumpulkan data rekap untuk satu penugasan guru-kelas.
     *
     * @return array{kelas: string, matpel: string, guru: string, nip: ?string, tahun_ajaran: string, kolom: array, siswas: array, rata_kolom: array, rata_umum: ?float}
     */
    public function kumpulkan(GuruKelas $guruKelas): array
    {
        $guruKelas->loadMissing(['kelas:id,nama', 'matpel:id,name', 'guru:id,nama_lengkap,nip', 'tahunAjaran:id,name']);

        $details = DetailPenilaian::with('penilaian:id,nama,nilai_maks')
            ->where('guru_kelas_id', $guruKelas->id)
            ->get();

        $kolom = $details
            ->groupBy('penilaian_id')
            ->map(fn ($group): array => [
                'id' => $group->first()->penilaian_id,
                'nama' => $group->first()->penilaian?->nama ?? 'Penilaian',
                'nilai_maks' => $group->first()->penilaian?->nilai_maks,
            ])
            ->values();

        $nilaiBySiswa = $details
            ->groupBy('siswa_nisn')
            ->map(fn ($group) => $group->mapWithKeys(fn ($d): array => [$d->penilaian_id => $d->nilai]));

        $siswas = SiswaKelas::query()
            ->with('siswa:nisn,nis,nama_lengkap')
            ->where('kelas_id', $guruKelas->kelas_id)
            ->where('tahun_ajaran_id', $guruKelas->tahun_ajaran_id)
            ->where('active', true)
            ->orderBy('siswa_nisn')
            ->get()
            ->map(function (SiswaKelas $sk) use ($nilaiBySiswa, $kolom): array {
                $row = $nilaiBySiswa->get($sk->siswa_nisn, collect());
                $nilai = $kolom->map(fn (array $k) => $row->get($k['id']));
                $terisi = $nilai->filter(fn ($n) => $n !== null);

                return [
                    'nisn' => $sk->siswa_nisn,
                    'nis' => $sk->siswa?->nis,
                    'nama' => $sk->siswa?->nama_lengkap ?? 'Siswa',
                    'nilai' => $nilai->values()->all(),
                    'rata_rata' => $terisi->count() ? round($terisi->avg(), 2) : null,
                ];
            })
            ->all();

        $rataKolom = [];
        foreach ($kolom as $j => $k) {
            $vals = array_filter(
                array_column(array_column($siswas, 'nilai'), $j),
                fn ($n) => $n !== null
            );
            $rataKolom[] = $vals ? round(array_sum($vals) / count($vals), 2) : '-';
        }
        $semuaRata = array_filter(array_column($siswas, 'rata_rata'), fn ($n) => $n !== null);

        return [
            'kelas' => $guruKelas->kelas?->nama ?? '-',
            'matpel' => $guruKelas->matpel?->name ?? '-',
            'guru' => $guruKelas->guru?->nama_lengkap ?? '-',
            'nip' => $guruKelas->guru?->nip,
            'tahun_ajaran' => $guruKelas->tahunAjaran?->name ?? '-',
            'kolom' => $kolom->all(),
            'siswas' => $siswas,
            'rata_kolom' => $rataKolom,
            'rata_umum' => $semuaRata ? round(array_sum($semuaRata) / count($semuaRata), 2) : null,
        ];
    }
}
