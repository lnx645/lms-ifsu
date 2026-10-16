<?php

namespace App\Http\Controllers;

use App\Models\GuruKelas;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MataPelajaranGuruController extends Controller
{
    /**
     * Halaman matpel saya: daftar mata pelajaran peran pengguna (guru/siswa).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('guru/Matpel', match ($user?->role) {
            'siswa' => $this->siswaProps($user),
            default => $this->guruProps($user),
        });
    }

    /**
     * Matpel yang diajar guru pada tahun ajaran aktif, lengkap dengan kelas & jumlah materi.
     */
    private function guruProps(User $user): array
    {
        $penugasan = GuruKelas::where('guru_id', $user->guru?->id)
            ->where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->with(['kelas', 'matpel'])
            ->get();

        $jumlahMateri = Materi::whereIn('guru_kelas_id', $penugasan->pluck('id'))
            ->selectRaw('guru_kelas_id, count(*) as total')
            ->groupBy('guru_kelas_id')
            ->pluck('total', 'guru_kelas_id');

        $matpels = $penugasan
            ->groupBy('matpel_id')
            ->map(function ($rows, $matpelId) use ($jumlahMateri): array {
                $matpel = $rows->first()->matpel;

                return [
                    'id' => $matpel?->id ?? (int) $matpelId,
                    'name' => $matpel?->name ?? 'Matpel',
                    'description' => $matpel?->description,
                    'kelas' => $rows->map(fn (GuruKelas $gk): array => [
                        'guru_kelas_id' => $gk->id,
                        'nama' => $gk->kelas?->nama ?? 'Kelas',
                    ])->values(),
                    'total_materi' => $rows->sum(fn (GuruKelas $gk) => (int) ($jumlahMateri[$gk->id] ?? 0)),
                ];
            })
            ->values()
            ->all();

        return [
            'role' => 'guru',
            'tahunAjaran' => $this->tahunAjaran?->name,
            'kelas' => null,
            'matpels' => $matpels,
        ];
    }

    /**
     * Matpel yang diajarkan di kelas siswa pada tahun ajaran aktif, lengkap dengan guru pengampu.
     */
    private function siswaProps(User $user): array
    {
        $kelas = $user->siswa?->kelas;

        $penugasan = GuruKelas::where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->when($kelas, fn ($q) => $q->where('kelas_id', $kelas->id))
            ->with(['kelas', 'matpel', 'guru'])
            ->get();

        $jumlahMateri = Materi::whereIn('guru_kelas_id', $penugasan->pluck('id'))
            ->selectRaw('guru_kelas_id, count(*) as total')
            ->groupBy('guru_kelas_id')
            ->pluck('total', 'guru_kelas_id');

        // Materi terbaru per mata pelajaran agar siswa tahu materi apa yang baru.
        $lastMateri = Materi::whereIn('guru_kelas_id', $penugasan->pluck('id'))
            ->join('guru_kelas', 'materis.guru_kelas_id', '=', 'guru_kelas.id')
            ->select('guru_kelas.matpel_id', 'materis.judul', 'materis.created_at')
            ->orderByDesc('materis.created_at')
            ->get()
            ->groupBy('matpel_id')
            ->map(fn ($group) => $group->first());

        $matpels = $penugasan
            ->groupBy('matpel_id')
            ->map(function ($rows, $matpelId) use ($jumlahMateri, $lastMateri): array {
                $matpel = $rows->first()->matpel;
                $last = $lastMateri->get($matpelId);

                return [
                    'id' => $matpel?->id ?? (int) $matpelId,
                    'name' => $matpel?->name ?? 'Matpel',
                    'description' => $matpel?->description,
                    'guru' => $rows->first()->guru?->nama_lengkap ?? 'Guru',
                    'total_materi' => $rows->sum(fn (GuruKelas $gk) => (int) ($jumlahMateri[$gk->id] ?? 0)),
                    'last_materi' => $last
                        ? [
                            'judul' => $last->judul,
                            'created_at' => $last->created_at?->translatedFormat('d M Y H:i'),
                            'is_baru' => $last->created_at?->gt(now()->subDays(7)) ?? false,
                            'sort_key' => $last->created_at?->timestamp ?? 0,
                        ]
                        : null,
                ];
            })
            ->sortByDesc(fn (array $m): int => $m['last_materi']['sort_key'] ?? 0)
            ->values()
            ->all();

        return [
            'role' => 'siswa',
            'tahunAjaran' => $this->tahunAjaran?->name,
            'kelas' => $kelas?->nama,
            'matpels' => $matpels,
        ];
    }
}
