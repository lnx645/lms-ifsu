<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\BaseAppController;
use App\Models\GuruKelas;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MateriController extends BaseAppController implements HasMiddleware
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
     * Tampilkan materi yang diterima siswa sesuai kelas & tahun ajaran aktif.
     */
    public function index(Request $request): Response
    {
        $kelas = $request->user()->siswa?->kelas;

        $query = Materi::whereHas('guruKelas', function ($query) use ($kelas): void {
            $query->where('aktif', true)
                ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
                ->when($kelas, fn ($q) => $q->where('kelas_id', $kelas->id));
        })
            ->with(['guruKelas.kelas', 'guruKelas.matpel', 'guru'])
            ->when($request->integer('matpel'), fn ($q, $matpelId) => $q->whereHas(
                'guruKelas',
                fn ($gk) => $gk->where('matpel_id', $matpelId)
            ))
            ->when($request->string('q')->toString(), fn ($q, $keyword) => $q->where(fn ($w) => $w
                ->where('judul', 'like', "%{$keyword}%")
                ->orWhere('deskripsi', 'like', "%{$keyword}%")));

        $materis = $query->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Materi $materi): array => [
                'id' => $materi->id,
                'judul' => $materi->judul,
                'deskripsi' => $materi->deskripsi,
                'file_name' => $materi->file_name,
                'file_size' => $materi->file_size,
                'kelas' => $materi->guruKelas?->kelas?->nama,
                'matpel' => $materi->guruKelas?->matpel?->name,
                'guru' => $materi->guru?->nama_lengkap,
                'dibuat_pada' => $materi->created_at?->translatedFormat('d M Y H:i'),
            ]);

        $matpelList = GuruKelas::where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->when($kelas, fn ($q) => $q->where('kelas_id', $kelas->id))
            ->with('matpel')
            ->get()
            ->pluck('matpel')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($matpel): array => ['value' => $matpel->id, 'label' => $matpel->name])
            ->all();

        return Inertia::render('siswa/Materi/Index', [
            'materis' => $materis,
            'kelas' => $kelas?->nama,
            'matpelList' => $matpelList,
            'filters' => [
                'matpel' => $request->integer('matpel') ?: null,
                'q' => $request->string('q')->toString(),
            ],
        ]);
    }

    /**
     * Tampilkan detail materi lengkap beserta pratinjau berkas (PDF).
     */
    public function show(Request $request, Materi $materi): Response
    {
        abort_unless($this->bolehAkses($request, $materi), 404);

        return Inertia::render('siswa/Materi/Detail', [
            'materi' => [
                'id' => $materi->id,
                'judul' => $materi->judul,
                'deskripsi' => $materi->deskripsi,
                'has_konten' => (bool) $materi->konten,
                'file_name' => $materi->file_name,
                'file_size' => $materi->file_size,
                'mime_type' => $materi->mime_type,
                'kelas' => $materi->guruKelas?->kelas?->nama,
                'matpel' => $materi->guruKelas?->matpel?->name,
                'guru' => $materi->guru?->nama_lengkap,
                'dibuat_pada' => $materi->created_at?->translatedFormat('d M Y H:i'),
            ],
            'konten' => Inertia::optional(fn () => $materi->konten),
        ]);
    }

    /**
     * Buka berkas materi di tab baru (inline).
     */
    public function lihat(Request $request, Materi $materi): StreamedResponse
    {
        abort_unless($this->bolehAkses($request, $materi), 404);
        abort_unless($materi->file_path, 404);

        return Storage::disk('public')->response($materi->file_path, $materi->file_name, [
            'Content-Disposition' => 'inline; filename="'.$materi->file_name.'"',
        ]);
    }

    /**
     * Unduh berkas materi.
     */
    public function unduh(Request $request, Materi $materi): StreamedResponse
    {
        abort_unless($this->bolehAkses($request, $materi), 404);
        abort_unless($materi->file_path, 404);

        return Storage::disk('public')->download($materi->file_path, $materi->file_name);
    }

    /**
     * Cek apakah materi dikirim ke kelas siswa pada tahun ajaran aktif.
     */
    private function bolehAkses(Request $request, Materi $materi): bool
    {
        $kelas = $request->user()->siswa?->kelas;

        return $kelas !== null
            && $materi->guru_kelas_id !== null
            && $materi->guruKelas?->kelas_id === $kelas->id
            && (int) $materi->guruKelas?->tahun_ajaran_id === (int) ($this->tahunAjaran?->id ?? 0)
            && (bool) $materi->guruKelas?->aktif === true;
    }
}
