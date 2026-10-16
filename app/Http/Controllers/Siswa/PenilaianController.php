<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\BaseAppController;
use App\Models\DetailPenilaian;
use App\Models\GuruKelas;
use App\Models\SiswaKelas;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class PenilaianController extends BaseAppController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, $next) {
                if ($request->user()?->role !== 'siswa') {
                    abort(403);
                }

                return $next($request);
            }),
        ];
    }

    /**
     * Rekap seluruh nilai siswa (tugas + penilaian manual) per mata pelajaran.
     */
    public function index(Request $request): Response
    {
        $siswa = $request->user()->siswa;

        $siswaKelas = SiswaKelas::where('siswa_nisn', $siswa->nisn)
            ->where('active', true)
            ->when($this->tahunAjaran, fn ($q, $ta) => $q->where('tahun_ajaran_id', $ta->id))
            ->first();

        $matpel = collect();

        if ($siswaKelas) {
            $guruKelasIds = GuruKelas::where('kelas_id', $siswaKelas->kelas_id)
                ->where('aktif', true)
                ->where('tahun_ajaran_id', $siswaKelas->tahun_ajaran_id)
                ->pluck('id');

            $tugases = Tugas::query()
                ->select(['id', 'guru_kelas_id', 'judul', 'poin'])
                ->whereIn('guru_kelas_id', $guruKelasIds)
                ->with(['pengumpulans' => fn ($q) => $q->where('siswa_nisn', $siswa->nisn)->select(['id', 'tugas_id', 'nilai'])])
                ->orderByDesc('created_at')
                ->get();

            $details = DetailPenilaian::query()
                ->select(['id', 'penilaian_id', 'guru_kelas_id', 'nilai', 'sumber'])
                ->with(['penilaian:id,nama,tipe,nilai_maks'])
                ->whereIn('guru_kelas_id', $guruKelasIds)
                ->where('siswa_nisn', $siswa->nisn)
                ->where('sumber', '!=', 'tugas')
                ->whereHas('penilaian', fn ($q) => $q->where('aktif', true))
                ->get();

            $guruKelasList = GuruKelas::whereIn('id', $guruKelasIds)
                ->with(['kelas:id,nama', 'matpel:id,name', 'guru:id,nama_lengkap'])
                ->orderBy('kelas_id')
                ->get();

            $matpel = $guruKelasList->map(function (GuruKelas $gk) use ($tugases, $details): array {
                $nilai = collect();

                $details->where('guru_kelas_id', $gk->id)->each(function (DetailPenilaian $d) use ($nilai): void {
                    $nilai->push([
                        'nama' => $d->penilaian?->nama ?? 'Penilaian',
                        'tipe' => $d->penilaian?->tipe,
                        'sumber' => $d->sumber,
                        'nilai' => $d->nilai,
                        'nilai_maks' => $d->penilaian?->nilai_maks,
                    ]);
                });

                $tugases->where('guru_kelas_id', $gk->id)->each(function (Tugas $t) use ($nilai): void {
                    $nilai->push([
                        'nama' => $t->judul,
                        'tipe' => 'tugas',
                        'sumber' => 'tugas',
                        'nilai' => $t->pengumpulans->first()?->nilai,
                        'nilai_maks' => $t->poin,
                    ]);
                });

                return [
                    'id' => $gk->id,
                    'kelas' => $gk->kelas?->nama,
                    'matpel' => $gk->matpel?->name,
                    'guru' => $gk->guru?->nama_lengkap,
                    'nilai' => $nilai->values(),
                ];
            });
        }

        return Inertia::render('siswa/Penilaian/Index', [
            'matpel' => $matpel->values(),
        ]);
    }
}
