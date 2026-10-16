<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\DetailPenilaian;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Penilaian;
use App\Models\SiswaKelas;
use App\Services\ExportNilaiService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PenilaianController extends BaseAppController implements HasMiddleware
{
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

    /**
     * Daftar jenis penilaian aktif + penugasan guru untuk input nilai.
     */
    public function index(Request $request): Response
    {
        $guru = $request->user()->guru;

        $penilaian = Penilaian::query()
            ->select(['id', 'nama', 'deskripsi', 'tipe', 'nilai_maks', 'aktif', 'sumber'])
            ->where('aktif', true)
            ->orderBy('nama')
            ->get()
            ->map(fn (Penilaian $p): array => [
                'id' => $p->id,
                'nama' => $p->nama,
                'deskripsi' => $p->deskripsi,
                'tipe' => $p->tipe,
                'nilai_maks' => $p->nilai_maks,
                'sumber' => $p->sumber,
            ]);

        return Inertia::render('guru/Penilaian/Index', [
            'penilaian' => $penilaian,
            'penugasan' => $this->penugasan($guru),
        ]);
    }

    /**
     * Rekap nilai (leger) per penugasan: siswa × penilaian + rata-rata.
     */
    public function rekap(Request $request): Response
    {
        $guru = $request->user()->guru;

        $guruKelasId = $request->integer('guru_kelas_id') ?: null;
        $guruKelasInfo = null;
        $kolom = null;
        $siswas = null;

        if ($guruKelasId) {
            $guruKelas = GuruKelas::with(['kelas:id,nama', 'matpel:id,name'])
                ->where('id', $guruKelasId)
                ->where('guru_id', $guru->id)
                ->firstOrFail();

            $guruKelasInfo = [
                'id' => $guruKelas->id,
                'kelas' => $guruKelas->kelas?->nama,
                'matpel' => $guruKelas->matpel?->name,
            ];

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
                ->with('siswa')
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
                        'nama' => $sk->siswa?->nama_lengkap ?? 'Siswa',
                        'nilai' => $nilai->values(),
                        'rata_rata' => $terisi->count() ? round($terisi->avg(), 2) : null,
                    ];
                });
        }

        return Inertia::render('guru/Penilaian/Rekap', [
            'penugasan' => $this->penugasan($guru),
            'guruKelasInfo' => $guruKelasInfo,
            'selectedGuruKelasId' => $guruKelasId,
            'kolom' => $kolom,
            'siswas' => $siswas,
        ]);
    }

    /**
     * Penugasan aktif guru untuk dropdown.
     */
    private function penugasan(Guru $guru): array
    {
        return GuruKelas::where('guru_id', $guru->id)
            ->where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->with(['kelas', 'matpel'])
            ->orderBy('kelas_id')
            ->get()
            ->map(fn (GuruKelas $gk): array => [
                'value' => $gk->id,
                'label' => ($gk->kelas?->nama ?? 'Kelas').' — '.($gk->matpel?->name ?? 'Matpel'),
            ])
            ->all();
    }

    /**
     * Tabel siswa + nilai untuk satu (penilaian, penugasan).
     */
    public function show(Request $request, Penilaian $penilaian, GuruKelas $guruKelas): Response
    {
        abort_unless($guruKelas->guru_id === $request->user()->guru?->id, 404);
        abort_unless($penilaian->aktif, 404);

        $detail = DetailPenilaian::where('penilaian_id', $penilaian->id)
            ->where('guru_kelas_id', $guruKelas->id)
            ->get()
            ->keyBy('siswa_nisn');

        $siswas = SiswaKelas::query()
            ->with('siswa')
            ->where('kelas_id', $guruKelas->kelas_id)
            ->where('tahun_ajaran_id', $guruKelas->tahun_ajaran_id)
            ->where('active', true)
            ->orderBy('siswa_nisn')
            ->get()
            ->map(fn (SiswaKelas $sk): array => [
                'nisn' => $sk->siswa_nisn,
                'nama' => $sk->siswa?->nama_lengkap ?? 'Siswa',
                'nilai' => $detail->get($sk->siswa_nisn)?->nilai,
                'sumber' => $detail->get($sk->siswa_nisn)?->sumber,
                'keterangan' => $detail->get($sk->siswa_nisn)?->keterangan,
            ]);

        return Inertia::render('guru/Penilaian/Input', [
            'penilaian' => [
                'id' => $penilaian->id,
                'nama' => $penilaian->nama,
                'deskripsi' => $penilaian->deskripsi,
                'tipe' => $penilaian->tipe,
                'nilai_maks' => $penilaian->nilai_maks,
                'sumber' => $penilaian->sumber,
            ],
            'guruKelas' => [
                'id' => $guruKelas->id,
                'kelas' => $guruKelas->kelas?->nama,
                'matpel' => $guruKelas->matpel?->name,
            ],
            'siswas' => $siswas,
        ]);
    }

    /**
     * Simpan atau perbarui nilai manual seorang siswa.
     */
    public function store(Request $request, Penilaian $penilaian, GuruKelas $guruKelas): RedirectResponse
    {
        abort_unless($guruKelas->guru_id === $request->user()->guru?->id, 404);
        abort_unless($penilaian->aktif, 404);

        $data = $request->validate([
            'siswa_nisn' => ['required', 'string', 'exists:siswa,nisn'],
            'nilai' => ['required', 'numeric', 'min:0', 'max:'.$penilaian->nilai_maks],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [], [
            'siswa_nisn' => 'Siswa',
            'nilai' => 'Nilai',
            'keterangan' => 'Keterangan',
        ]);

        // Pastikan siswa terdaftar di kelas yang diampu guru ini.
        $terdaftar = SiswaKelas::where('siswa_nisn', $data['siswa_nisn'])
            ->where('kelas_id', $guruKelas->kelas_id)
            ->where('tahun_ajaran_id', $guruKelas->tahun_ajaran_id)
            ->exists();

        abort_unless($terdaftar, 404, 'Siswa tidak terdaftar di kelas ini.');

        DetailPenilaian::updateOrCreate(
            [
                'penilaian_id' => $penilaian->id,
                'guru_kelas_id' => $guruKelas->id,
                'siswa_nisn' => $data['siswa_nisn'],
            ],
            [
                'tahun_ajaran_id' => $guruKelas->tahun_ajaran_id,
                'guru_id' => $guruKelas->guru_id,
                'nilai' => $data['nilai'],
                'sumber' => 'manual',
                'keterangan' => $data['keterangan'] ?? null,
            ]
        );

        Toast::success('Nilai berhasil disimpan.');

        return Redirect::back();
    }

    /**
     * Export daftar nilai per kelas per matpel ke .xlsx
     * (gaya laporan pendidikan Indonesia).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $guru = $request->user()->guru;

        $guruKelas = GuruKelas::with(['kelas:id,nama', 'matpel:id,name'])
            ->where('id', $request->integer('guru_kelas_id'))
            ->where('guru_id', $guru->id)
            ->firstOrFail();

        $path = app(ExportNilaiService::class)->export($guruKelas);

        $namaFile = 'daftar-nilai-'
            .Str::slug(($guruKelas->kelas?->nama ?? 'kelas').'-'.($guruKelas->matpel?->name ?? 'matpel'))
            .'-'.date('Y-m-d').'.xlsx';

        return response()->download($path, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
