<?php

namespace App\Http\Controllers;

use App\Models\GuruKelas;
use App\Models\Materi;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends BaseAppController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $props = match ($user?->role) {
            'siswa' => $this->siswaProps($user),
            'guru' => ['nama' => $user->guru?->nama_lengkap],
            default => [],
        };

        return Inertia::render('Dashboard', $props);
    }

    /**
     * Data dashboard siswa: sambutan, kutipan, ringkasan matpel, materi terbaru.
     */
    private function siswaProps(User $user): array
    {
        $siswa = $user->siswa;
        $kelas = $siswa?->kelas;

        $scoped = function ($query) use ($kelas) {
            return $query->whereHas('guruKelas', function ($q) use ($kelas): void {
                $q->where('aktif', true)
                    ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
                    ->when($kelas, fn ($w) => $w->where('kelas_id', $kelas->id));
            });
        };

        $materiTerbaru = $scoped(Materi::query()->with(['guruKelas.matpel', 'guru']))
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn (Materi $materi): array => [
                'id' => $materi->id,
                'judul' => $materi->judul,
                'matpel' => $materi->guruKelas?->matpel?->name,
                'guru' => $materi->guru?->nama_lengkap,
                'dibuat_pada' => $materi->created_at?->translatedFormat('d M Y'),
            ])
            ->all();

        $ringkasan = Materi::query()
            ->selectRaw('guru_kelas.matpel_id, matpels.name as matpel, count(*) as total')
            ->join('guru_kelas', 'materis.guru_kelas_id', '=', 'guru_kelas.id')
            ->join('matpels', 'guru_kelas.matpel_id', '=', 'matpels.id')
            ->where('guru_kelas.aktif', true)
            ->where('guru_kelas.tahun_ajaran_id', $this->tahunAjaran?->id)
            ->when($kelas, fn ($q) => $q->where('guru_kelas.kelas_id', $kelas->id))
            ->groupBy('guru_kelas.matpel_id', 'matpels.name')
            ->orderBy('matpels.name')
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->matpel_id,
                'matpel' => $row->matpel,
                'total' => (int) $row->total,
            ])
            ->all();

        $guruKelasIds = GuruKelas::where('kelas_id', $kelas?->id)
            ->where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->pluck('id');

        $tugasBelum = Tugas::query()
            ->select(['id', 'guru_kelas_id', 'judul', 'deadline'])
            ->whereIn('guru_kelas_id', $guruKelasIds)
            ->where(fn ($q) => $q
                ->whereNull('tanggal_terbit')
                ->orWhere('tanggal_terbit', '<=', now()))
            ->where(fn ($q) => $q
                ->whereNull('deadline')
                ->orWhere('deadline', '>', now()))
            ->whereDoesntHave('pengumpulans', fn ($q) => $q->where('siswa_nisn', $siswa->nisn))
            ->with(['guruKelas.matpel:id,name'])
            ->orderBy('deadline')
            ->take(5)
            ->get()
            ->map(fn (Tugas $tugas): array => [
                'id' => $tugas->id,
                'judul' => $tugas->judul,
                'matpel' => $tugas->guruKelas?->matpel?->name,
                'deadline' => $tugas->deadline?->translatedFormat('d M Y H:i'),
            ])
            ->all();

        return [
            'nama' => $siswa?->nama_lengkap,
            'kelas' => $kelas?->nama,
            'tahunAjaran' => $this->tahunAjaran?->name,
            'kutipan' => $this->kutipanAcak(),
            'materiTerbaru' => $materiTerbaru,
            'ringkasan' => $ringkasan,
            'tugasBelum' => $tugasBelum,
        ];
    }

    /**
     * Kutipan motivasi acak untuk dashboard.
     */
    private function kutipanAcak(): array
    {
        $kutipan = [
            ['teks' => 'Ilmu itu diperoleh dengan belajar, bukan dengan angan-angan.', 'penulis' => 'Ali bin Abi Thalib'],
            ['teks' => 'Barang siapa menempuh jalan untuk mencari ilmu, maka Allah mudahkan baginya jalan menuju surga.', 'penulis' => 'HR. Muslim'],
            ['teks' => 'Belajarlah dari kemarin, hiduplah untuk hari ini, berharaplah untuk besok.', 'penulis' => 'Albert Einstein'],
            ['teks' => 'Sukses adalah jumlah dari usaha kecil yang diulangi setiap hari.', 'penulis' => 'Robert Collier'],
            ['teks' => 'Jangan pernah berhenti belajar, karena hidup tidak pernah berhenti mengajar.', 'penulis' => 'Anonim'],
            ['teks' => 'Sesungguhnya bersama kesulitan ada kemudahan.', 'penulis' => 'QS. Al-Insyirah: 6'],
            ['teks' => 'Kesuksesan berawal dari kemauan untuk mencoba.', 'penulis' => 'Anonim'],
            ['teks' => 'Orang bijak belajar dari kesalahan orang lain, orang bodoh belajar dari kesalahannya sendiri.', 'penulis' => 'Pepatah'],
            ['teks' => 'Tekad yang kuat mengalahkan bakat yang luar biasa.', 'penulis' => 'Anonim'],
            ['teks' => 'Masa depan adalah milik mereka yang belajar lebih banyak keterampilan.', 'penulis' => 'Carl Rogers'],
        ];

        return $kutipan[array_rand($kutipan)];
    }
}
