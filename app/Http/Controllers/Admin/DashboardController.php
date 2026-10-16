<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $tahunAjaranParam = $request->input('tahun_ajaran');

        if ($tahunAjaranParam === null) {
            $tahunAjaranId = $this->tahunAjaran?->id;
        } elseif ($tahunAjaranParam === '' || $tahunAjaranParam === 'all') {
            $tahunAjaranId = null;
        } else {
            $tahunAjaranId = (int) $tahunAjaranParam;
        }

        return Inertia::render('admin/Index', [
            'stats' => $this->kpiStats($tahunAjaranId),
            'statsTambahan' => $this->statsTambahan($tahunAjaranId),
            'siswaPerJurusan' => $this->siswaPerJurusan($tahunAjaranId),
            'kelasPerJurusan' => $this->kelasPerJurusan(),
            'siswaPerKelas' => $this->siswaPerKelas($tahunAjaranId),
            'matpelTeratas' => $this->matpelTeratas($tahunAjaranId),
            'guruTeraktif' => $this->guruTeraktif($tahunAjaranId),
            'waliKelas' => $this->waliKelas(),
            'penugasanPerTahun' => $this->penugasanPerTahun(),
            'tahunAjaranOptions' => TahunAjaran::orderByDesc('active')
                ->orderByDesc('name')
                ->get(['id', 'name', 'active']),
            'tahunAjaranTerpilih' => $tahunAjaranId,
        ]);
    }

    /**
     * Summary of the main entities as KPI cards.
     *
     * @return array<string, int>
     */
    private function kpiStats(?int $tahunAjaranId): array
    {
        $penugasanBase = GuruKelas::query()
            ->when($tahunAjaranId, fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId));

        // Menggunakan true/false untuk PostgreSQL
        $penugasanStats = (clone $penugasanBase)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN aktif = true THEN 1 ELSE 0 END) as aktif')
            ->first();

        $siswaDenganKelas = SiswaKelas::query()
            ->where('active', true)
            ->when($tahunAjaranId, fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->distinct('siswa_nisn')
            ->count('siswa_nisn');

        return [
            'guru' => Guru::count(),
            'guru_aktif' => Guru::where('is_aktif', true)->count(),
            'siswa' => Siswa::count(),
            'siswa_dengan_kelas' => $siswaDenganKelas,
            'kelas' => Kelas::leaf()->count(),
            'matpel' => Matpel::count(),
            'jurusan' => Jurusan::count(),
            'penugasan' => $penugasanStats->total ?? 0,
            'penugasan_aktif' => $penugasanStats->aktif ?? 0,
        ];
    }

    /**
     * Secondary statistics (gender ratio, accounts, feature flags).
     *
     * @return array<string, int>
     */
    private function statsTambahan(?int $tahunAjaranId): array
    {
        $penugasanStats = GuruKelas::query()
            ->when($tahunAjaranId, fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->selectRaw('COUNT(DISTINCT guru_id) as guru_dengan_penugasan')
            ->first();
        $userStats = User::selectRaw('
            SUM(CASE WHEN guru_id IS NOT NULL THEN 1 ELSE 0 END) as guru_akun,
            SUM(CASE WHEN nisn IS NOT NULL THEN 1 ELSE 0 END) as siswa_akun
        ')->first();

        return [
            'siswa_l' => Siswa::where('jenis_kelamin', 'L')->count(),
            'siswa_p' => Siswa::where('jenis_kelamin', 'P')->count(),
            'guru_l' => Guru::where('jenis_kelamin', 'L')->count(),
            'guru_p' => Guru::where('jenis_kelamin', 'P')->count(),
            'guru_dengan_penugasan' => $penugasanStats->guru_dengan_penugasan ?? 0,
            'kelas_aktif' => Kelas::leaf()->where('active', true)->count(),
            'guru_dengan_akun' => $userStats->guru_akun ?? 0,
            'siswa_dengan_akun' => $userStats->siswa_akun ?? 0,
        ];
    }

    /**
     * Number of students currently assigned per jurusan.
     *
     * @return array<int, array{name: string, kode: string|null, siswa: int}>
     */
    private function siswaPerJurusan(?int $tahunAjaranId): array
    {
        return Jurusan::query()
            ->leftJoin('kelas', 'kelas.jurusan_id', '=', 'jurusans.id')
            ->leftJoin('siswa_kelas', function ($join) use ($tahunAjaranId): void {
                $join->on('siswa_kelas.kelas_id', '=', 'kelas.id')
                    ->where('siswa_kelas.active', true)
                    ->when($tahunAjaranId, fn ($query) => $query->where('siswa_kelas.tahun_ajaran_id', $tahunAjaranId));
            })
            ->selectRaw('jurusans.name, jurusans.kode, COUNT(DISTINCT siswa_kelas.siswa_nisn) as siswa')
            ->groupBy('jurusans.id', 'jurusans.name', 'jurusans.kode')
            ->orderByDesc('siswa')
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->name,
                'kode' => $row->kode,
                'siswa' => (int) $row->siswa,
            ])
            ->all();
    }

    /**
     * Number of classes (leaf only) per jurusan.
     *
     * @return array<int, array{name: string, kode: string|null, kelas: int}>
     */
    private function kelasPerJurusan(): array
    {
        $parentIds = Kelas::whereNotNull('parent_id')->select('parent_id');

        return Jurusan::query()
            ->leftJoin('kelas', 'kelas.jurusan_id', '=', 'jurusans.id')
            ->whereNotIn('kelas.id', $parentIds)
            ->selectRaw('jurusans.name, jurusans.kode, COUNT(DISTINCT kelas.id) as kelas')
            ->groupBy('jurusans.id', 'jurusans.name', 'jurusans.kode')
            ->orderByDesc('kelas')
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->name,
                'kode' => $row->kode,
                'kelas' => (int) $row->kelas,
            ])
            ->all();
    }

    /**
     * Number of active students per class (leaf classes with students).
     *
     * @return array<int, array{nama: string, siswa: int}>
     */
    private function siswaPerKelas(?int $tahunAjaranId): array
    {
        return Kelas::leaf()
            ->leftJoin('siswa_kelas', function ($join) use ($tahunAjaranId): void {
                $join->on('siswa_kelas.kelas_id', '=', 'kelas.id')
                    ->where('siswa_kelas.active', true)
                    ->when($tahunAjaranId, fn ($query) => $query->where('siswa_kelas.tahun_ajaran_id', $tahunAjaranId));
            })
            ->selectRaw('kelas.nama, COUNT(DISTINCT siswa_kelas.siswa_nisn) as siswa')
            ->havingRaw('COUNT(DISTINCT siswa_kelas.siswa_nisn) > 0')
            ->groupBy('kelas.id', 'kelas.nama')
            ->orderByDesc('siswa')
            ->orderBy('kelas.nama')
            ->get()
            ->map(fn (object $row): array => [
                'nama' => $row->nama,
                'siswa' => (int) $row->siswa,
            ])
            ->all();
    }

    /**
     * Subjects with the most teaching assignments (top 5).
     *
     * @return array<int, array{name: string, penugasan: int}>
     */
    private function matpelTeratas(?int $tahunAjaranId): array
    {
        return Matpel::query()
            ->leftJoin('guru_kelas', function ($join) use ($tahunAjaranId): void {
                $join->on('guru_kelas.matpel_id', '=', 'matpels.id')
                    ->when($tahunAjaranId, fn ($query) => $query->where('guru_kelas.tahun_ajaran_id', $tahunAjaranId));
            })
            ->selectRaw('matpels.name, COUNT(guru_kelas.id) as penugasan')
            ->groupBy('matpels.id', 'matpels.name')
            ->orderByDesc('penugasan')
            ->orderBy('matpels.name')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->name,
                'penugasan' => (int) $row->penugasan,
            ])
            ->all();
    }

    /**
     * Teachers with the most active teaching assignments (top 5).
     *
     * @return array<int, array{nama: string, penugasan: int}>
     */
    private function guruTeraktif(?int $tahunAjaranId): array
    {
        return Guru::query()
            ->leftJoin('guru_kelas', function ($join) use ($tahunAjaranId): void {
                $join->on('guru_kelas.guru_id', '=', 'gurus.id')
                    ->when($tahunAjaranId, fn ($query) => $query->where('guru_kelas.tahun_ajaran_id', $tahunAjaranId));
            })
            ->selectRaw('gurus.nama_lengkap, COUNT(guru_kelas.id) as penugasan')
            ->havingRaw('COUNT(guru_kelas.id) > 0')
            ->groupBy('gurus.id', 'gurus.nama_lengkap')
            ->orderByDesc('penugasan')
            ->orderBy('gurus.nama_lengkap')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => [
                'nama' => $row->nama_lengkap,
                'penugasan' => (int) $row->penugasan,
            ])
            ->all();
    }

    /**
     * Wali kelas coverage across all classes.
     *
     * @return array{dengan_walikelas: int, tanpa_walikelas: int}
     */
    private function waliKelas(): array
    {
        $dengan = Kelas::leaf()->whereNotNull('guru_id')->count();

        return [
            'dengan_walikelas' => $dengan,
            'tanpa_walikelas' => max(Kelas::leaf()->count() - $dengan, 0),
        ];
    }

    /**
     * Active teaching assignments grouped by year.
     *
     * @return array<int, array{name: string, active: bool, penugasan: int}>
     */
    private function penugasanPerTahun(): array
    {
        return TahunAjaran::query()
            ->leftJoin('guru_kelas', 'guru_kelas.tahun_ajaran_id', '=', 'tahun_ajaran.id')
            ->selectRaw('tahun_ajaran.name, tahun_ajaran.active, COUNT(guru_kelas.id) as penugasan')
            ->groupBy('tahun_ajaran.id', 'tahun_ajaran.name', 'tahun_ajaran.active')
            ->orderByDesc('tahun_ajaran.active')
            ->orderByDesc('penugasan')
            ->orderBy('tahun_ajaran.name')
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->name,
                'active' => (bool) $row->active,
                'penugasan' => (int) $row->penugasan,
            ])
            ->all();
    }
}
