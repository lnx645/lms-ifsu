<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\SesiUjian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\AnalisisButirSoalService;
use App\Services\NotifikasiService;
use App\Services\SoalImportService;
use App\Services\UjianService;
use App\Support\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Fitur akademik penunjang ujian: kartu peserta, berita acara, analisis
 * butir soal, import soal, remedial, duplikasi, daftar hadir, ruang &
 * pengawas, reset pengerjaan.
 */
class UjianAkademikController extends BaseAppController implements HasMiddleware
{
    public function __construct(
        private readonly UjianService $ujianService,
        private readonly AnalisisButirSoalService $analisis,
        private readonly SoalImportService $importer,
        private readonly NotifikasiService $notifikasi,
    ) {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, $next) {
                if ($request->user()?->role !== 'guru') {
                    abort(403);
                }

                return $next($request);
            }),
        ];
    }

    private function milikGuru(Request $request, Ujian $ujian): void
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
    }

    // ------------------------------------------------------------------
    // 1. Kartu peserta ujian (PDF per sesi)
    // ------------------------------------------------------------------


    /**
     * Daftar siswa peserta sesi: yang dijatahkan (terjadwal) atau
     * seluruh siswa kelas (bebas).
     *
     * @return Collection<int, Siswa>
     */
    private function pesertaSesi(Ujian $ujian, SesiUjian $sesi): Collection
    {
        $kelasId = $ujian->guruKelas?->kelas_id;

        $query = Siswa::query()->orderBy('nama_lengkap');

        if ($ujian->sesiTerjadwal()) {
            $nisns = $sesi->pesertas()->pluck('siswa_nisn');
            $query->whereIn('nisn', $nisns);
        } elseif ($kelasId !== null) {
            $nisns = SiswaKelas::where('kelas_id', $kelasId)
                ->where('active', true)
                ->pluck('siswa_nisn');
            $query->whereIn('nisn', $nisns);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->get();
    }

    // ------------------------------------------------------------------
    // 2. Berita acara ujian
    // ------------------------------------------------------------------




    /**
     * Hitung statistik kehadiran & pelanggaran dari data pengerjaan.
     *
     * @return array<string, int>
     */

    // ------------------------------------------------------------------
    // 3. Analisis butir soal
    // ------------------------------------------------------------------

    public function analisisIndex(Request $request, Ujian $ujian): InertiaResponse
    {
        $this->milikGuru($request, $ujian);

        $hasil = $this->analisis->analisis($ujian);

        return Inertia::render('guru/Ujian/Analisis', [
            'ujian' => ['id' => $ujian->id, 'judul' => $ujian->judul],
            'hasil' => $hasil,
        ]);
    }

    // ------------------------------------------------------------------
    // 5. Import soal dari Excel
    // ------------------------------------------------------------------

    public function importTemplate(Request $request, Ujian $ujian): BinaryFileResponse
    {
        $this->milikGuru($request, $ujian);

        $path = tempnam(sys_get_temp_dir(), 'template-soal-').'.xlsx';
        $this->importer->buatTemplate($path);

        return response()->download($path, 'template-import-soal.xlsx')->deleteFileAfterSend();
    }

    public function importStore(Request $request, Ujian $ujian): RedirectResponse
    {
        $this->milikGuru($request, $ujian);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ]);

        $path = $data['file']->getRealPath();
        $hasil = $this->importer->impor($ujian, $path);

        if ($hasil['berhasil'] > 0) {
            Toast::success("{$hasil['berhasil']} soal berhasil diimpor.");
        }

        foreach (array_slice($hasil['errors'], 0, 5) as $error) {
            Toast::error($error);
        }

        if ($hasil['gagal'] > 5) {
            Toast::warning('... dan '.($hasil['gagal'] - 5).' baris gagal lainnya.');
        }

        return Redirect::back();
    }

    // ------------------------------------------------------------------
    // 6. Remedial
    // ------------------------------------------------------------------

    public function remedialIndex(Request $request, Ujian $ujian): InertiaResponse
    {
        $this->milikGuru($request, $ujian);

        $nilaiTerbaik = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('is_remedial', false)
            ->whereIn('status', ['selesai', 'auto_submit'])
            ->selectRaw('siswa_nisn, MAX(nilai_total) as nilai')
            ->groupBy('siswa_nisn')
            ->pluck('nilai', 'siswa_nisn');

        $siswaList = $this->siswaKelas($ujian);
        $butuhRemedial = [];

        foreach ($siswaList as $siswa) {
            $nilai = $nilaiTerbaik->get($siswa->nisn);

            if ($ujian->kkm !== null && $nilai !== null && $nilai < $ujian->kkm) {
                $butuhRemedial[] = [
                    'nisn' => $siswa->nisn,
                    'nama' => $siswa->nama_lengkap,
                    'nilai' => round((float) $nilai, 1),
                    'sudah_remedial' => UjianPengerjaan::where('ujian_id', $ujian->id)
                        ->where('siswa_nisn', $siswa->nisn)
                        ->where('is_remedial', true)
                        ->exists(),
                ];
            }
        }

        return Inertia::render('guru/Ujian/Remedial', [
            'ujian' => ['id' => $ujian->id, 'judul' => $ujian->judul, 'kkm' => $ujian->kkm],
            'butuhRemedial' => $butuhRemedial,
        ]);
    }

    public function remedialKkm(Request $request, Ujian $ujian): RedirectResponse
    {
        $this->milikGuru($request, $ujian);

        $data = $request->validate(['kkm' => ['nullable', 'integer', 'min:0', 'max:100']]);
        $ujian->update(['kkm' => $data['kkm']]);

        Toast::success('KKM diperbarui.');

        return Redirect::back();
    }

    /**
     * @return Collection<int, Siswa>
     */
    private function siswaKelas(Ujian $ujian): Collection
    {
        $kelasId = $ujian->guruKelas?->kelas_id;

        if ($kelasId === null) {
            return collect();
        }

        $nisns = SiswaKelas::where('kelas_id', $kelasId)
            ->where('active', true)
            ->pluck('siswa_nisn');

        return Siswa::whereIn('nisn', $nisns)->orderBy('nama_lengkap')->get();
    }

    // ------------------------------------------------------------------
    // 7. Duplikasi ujian
    // ------------------------------------------------------------------

    public function duplikat(Request $request, Ujian $ujian): RedirectResponse
    {
        $this->milikGuru($request, $ujian);

        $baru = $this->ujianService->duplikat($ujian);

        Toast::success('Ujian diduplikat sebagai draft: '.$baru->judul);

        return Redirect::route('app.guru.ujian.edit', $baru);
    }

    // ------------------------------------------------------------------
    // 8. Daftar hadir
    // ------------------------------------------------------------------

    public function daftarHadir(Request $request, Ujian $ujian, SesiUjian $sesi): HttpResponse
    {
        $this->milikGuru($request, $ujian);
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        $pesertas = $this->pesertaSesi($ujian, $sesi);

        $hadirNisns = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('sesi_ujian_id', $sesi->id)
            ->distinct()->pluck('siswa_nisn')->all();

        $pdf = Pdf::loadView('pdf.daftar-hadir', [
            'ujian' => $ujian,
            'sesi' => $sesi,
            'pesertas' => $pesertas,
            'hadirNisns' => $hadirNisns,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("daftar-hadir-{$sesi->nama}.pdf");
    }

    // ------------------------------------------------------------------
    // 9. Ruang ujian & pengawas
    // ------------------------------------------------------------------


    // ------------------------------------------------------------------
    // 10. Reset pengerjaan siswa
    // ------------------------------------------------------------------

    public function resetPengerjaan(Request $request, Ujian $ujian, UjianPengerjaan $pengerjaan): RedirectResponse
    {
        $this->milikGuru($request, $ujian);
        abort_unless($pengerjaan->ujian_id === $ujian->id, 404);

        DB::transaction(function () use ($pengerjaan): void {
            $pengerjaan->jawabans()->delete();
            $pengerjaan->pelanggarans()->delete();
            $pengerjaan->delete();
        });

        Toast::success('Pengerjaan siswa direset; siswa dapat mengulang.');

        return Redirect::back();
    }

    // ------------------------------------------------------------------
    // 12. Notifikasi: kirim pengumuman jadwal
    // ------------------------------------------------------------------

    public function kirimNotifikasiJadwal(Request $request, Ujian $ujian): RedirectResponse
    {
        $this->milikGuru($request, $ujian);

        $jumlah = $this->notifikasi->jadwalUjian($ujian);

        Toast::success("Notifikasi jadwal dikirim ke {$jumlah} siswa.");

        return Redirect::back();
    }
}
