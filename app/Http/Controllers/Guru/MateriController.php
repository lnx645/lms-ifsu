<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Materi;
use App\Models\TahunAjaran;
use App\Support\HtmlSanitizer;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MateriController extends BaseAppController implements HasMiddleware
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
     * Tampilkan daftar materi milik guru beserta form unggah.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('guru/Materi/Index', $this->indexData($request) + ['katalog' => null]);
    }

    /**
     * Data dasar halaman guru materi (dipakai index & katalog agar tidak crash saat render penuh).
     */
    private function indexData(Request $request): array
    {
        $guru = $request->user()->guru;

        $penugasan = GuruKelas::where('guru_id', $guru->id)
            ->where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->with(['kelas', 'matpel'])
            ->orderBy('kelas_id')
            ->get()
            ->map(fn (GuruKelas $gk): array => [
                'value' => $gk->id,
                'label' => ($gk->kelas?->nama ?? 'Kelas').' — '.($gk->matpel?->name ?? 'Matpel'),
            ]);

        $materis = Materi::query()
            ->select(['id', 'guru_id', 'guru_kelas_id', 'judul', 'deskripsi', 'file_name', 'file_size', 'created_at'])
            ->with(['guruKelas.kelas', 'guruKelas.matpel'])
            ->where('guru_id', $guru->id)
            ->when($request->integer('guru_kelas_id'), fn ($q, $gkId) => $q->where('guru_kelas_id', $gkId))
            ->when($request->string('q')->toString(), fn ($q, $keyword) => $q->where(fn ($w) => $w
                ->where('judul', 'like', "%{$keyword}%")
                ->orWhere('deskripsi', 'like', "%{$keyword}%")
                ->orWhere('konten', 'like', "%{$keyword}%")))
            ->orderByDesc('created_at')
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
                'dibuat_pada' => $materi->created_at?->translatedFormat('d M Y H:i'),
            ]);

        return [
            'guru_kelas_id' => $request->query('guru_kelas_id'),
            'materis' => $materis,
            'penugasan' => $penugasan,
            'katalogFilters' => $this->katalogFilters($guru),
            'filters' => [
                'guru_kelas_id' => $request->integer('guru_kelas_id') ?: null,
                'q' => $request->string('q')->toString(),
            ],
        ];
    }

    /**
     * Opsi filter katalog: hanya kelas/matpel yang pernah diajar guru (per tahun ajaran).
     */
    private function katalogFilters(Guru $guru): array
    {
        $rows = GuruKelas::where('guru_id', $guru->id)
            ->with(['kelas', 'matpel'])
            ->get()
            ->map(fn (GuruKelas $gk): array => [
                'tahun_ajaran_id' => $gk->tahun_ajaran_id,
                'kelas_id' => $gk->kelas_id,
                'matpel_id' => $gk->matpel_id,
                'kelas_nama' => $gk->kelas?->nama ?? 'Kelas',
                'matpel_nama' => $gk->matpel?->name ?? 'Matpel',
            ]);

        $kelas = [];
        $matpel = [];

        foreach ($rows as $row) {
            if ($row['kelas_id']) {
                $kelas[$row['kelas_id']] ??= [
                    'value' => $row['kelas_id'],
                    'label' => $row['kelas_nama'],
                    'taIds' => [],
                    'matpelIds' => [],
                ];
                $kelas[$row['kelas_id']]['taIds'][] = $row['tahun_ajaran_id'];
                if ($row['matpel_id']) {
                    $kelas[$row['kelas_id']]['matpelIds'][] = $row['matpel_id'];
                }
            }

            if ($row['matpel_id']) {
                $matpel[$row['matpel_id']] ??= [
                    'value' => $row['matpel_id'],
                    'label' => $row['matpel_nama'],
                    'taIds' => [],
                    'kelasIds' => [],
                ];
                $matpel[$row['matpel_id']]['taIds'][] = $row['tahun_ajaran_id'];
                if ($row['kelas_id']) {
                    $matpel[$row['matpel_id']]['kelasIds'][] = $row['kelas_id'];
                }
            }
        }

        return [
            'tahunAjaran' => TahunAjaran::orderByDesc('id')->get()
                ->map(fn (TahunAjaran $ta): array => ['value' => $ta->id, 'label' => $ta->name]),
            'kelas' => array_values($kelas),
            'matpel' => array_values($matpel),
        ];
    }

    /**
     * Katalog materi dari semua guru untuk disalin (dipakai lewat modal).
     */
    public function katalog(Request $request): Response
    {
        $katalog = Materi::query()
            ->with(['guruKelas.kelas', 'guruKelas.matpel', 'guruKelas.tahunAjaran', 'guru'])
            ->when($request->integer('tahun_ajaran_id'), fn ($q, $taId) => $q->whereHas(
                'guruKelas',
                fn ($w) => $w->where('tahun_ajaran_id', $taId),
            ))
            ->when($request->integer('kelas_id'), fn ($q, $kelasId) => $q->whereHas(
                'guruKelas',
                fn ($w) => $w->where('kelas_id', $kelasId),
            ))
            ->when($request->integer('matpel_id'), fn ($q, $matpelId) => $q->whereHas(
                'guruKelas',
                fn ($w) => $w->where('matpel_id', $matpelId),
            ))
            ->when($request->string('q')->toString(), fn ($q, $keyword) => $q->where(fn ($w) => $w
                ->where('judul', 'like', "%{$keyword}%")
                ->orWhere('deskripsi', 'like', "%{$keyword}%")))
            ->orderByDesc('created_at')
            ->paginate(8)
            ->withQueryString()
            ->through(fn (Materi $materi): array => [
                'id' => $materi->id,
                'judul' => $materi->judul,
                'deskripsi' => $materi->deskripsi,
                'file_name' => $materi->file_name,
                'kelas' => $materi->guruKelas?->kelas?->nama,
                'matpel' => $materi->guruKelas?->matpel?->name,
                'tahun_ajaran' => $materi->guruKelas?->tahunAjaran?->name,
                'guru' => $materi->guru?->nama_lengkap,
            ]);

        return Inertia::render('guru/Materi/Index', $this->indexData($request) + ['katalog' => $katalog]);
    }

    /**
     * Salin materi dari katalog ke penugasan guru.
     */
    public function salin(Request $request): RedirectResponse
    {
        $guru = $request->user()->guru;

        $data = $request->validate([
            'guru_kelas_id' => [
                'required',
                Rule::exists('guru_kelas', 'id')
                    ->where('guru_id', $guru->id)
                    ->where('aktif', true)
                    ->where('tahun_ajaran_id', $this->tahunAjaran?->id),
            ],
            'materi_id' => ['required', 'exists:materis,id'],
        ], [], [
            'guru_kelas_id' => 'Kelas & Mata Pelajaran',
            'materi_id' => 'Materi sumber',
        ]);

        $sumber = Materi::findOrFail($data['materi_id']);

        $filePath = null;
        if ($sumber->file_path) {
            $filePath = 'materi/'.Str::uuid().'.'.pathinfo($sumber->file_name, PATHINFO_EXTENSION);
            Storage::disk('public')->copy($sumber->file_path, $filePath);
        }

        $materi = Materi::create([
            'guru_id' => $guru->id,
            'guru_kelas_id' => $data['guru_kelas_id'],
            'judul' => $sumber->judul,
            'deskripsi' => $sumber->deskripsi,
            'konten' => $sumber->konten,
            'file_path' => $filePath,
            'file_name' => $sumber->file_name,
            'file_size' => $sumber->file_size,
            'mime_type' => $sumber->mime_type,
        ]);

        Toast::success('Materi "'.$materi->judul.'" berhasil disalin.');

        return Redirect::back();
    }

    /**
     * Data materi milik guru untuk modal edit.
     */
    public function edit(Request $request, Materi $materi): Response
    {
        abort_unless($materi->guru_id === $request->user()->guru?->id, 404);

        return Inertia::render('guru/Materi/Index', $this->indexData($request) + [
            'editMateri' => [
                'id' => $materi->id,
                'guru_kelas_id' => $materi->guru_kelas_id,
                'judul' => $materi->judul,
                'deskripsi' => $materi->deskripsi,
                'konten' => $materi->konten,
                'file_name' => $materi->file_name,
                'file_size' => $materi->file_size,
            ],
        ]);
    }

    /**
     * Perbarui materi milik guru; berkas diganti hanya jika diunggah ulang.
     */
    public function update(Request $request, Materi $materi): RedirectResponse
    {
        if ($materi->guru_id !== $request->user()->guru?->id) {
            Toast::error('Materi tidak ditemukan.');

            return Redirect::back();
        }

        $data = $this->validateMateriData($request);

        $file = $request->file('file');

        if ($file) {
            if ($materi->file_path) {
                Storage::disk('public')->delete($materi->file_path);
            }

            $materi->file_path = $file->store('materi', 'public');
            $materi->file_name = $file->getClientOriginalName();
            $materi->file_size = $file->getSize();
            $materi->mime_type = $file->getMimeType();
        }

        $materi->fill([
            'guru_kelas_id' => $data['guru_kelas_id'],
            'judul' => $data['judul'],
            'deskripsi' => HtmlSanitizer::clean($data['deskripsi'] ?? null),
            'konten' => HtmlSanitizer::clean($data['konten'] ?? null),
        ])->save();

        Toast::success('Materi "'.$materi->judul.'" berhasil diperbarui.');

        return Redirect::back();
    }

    /**
     * Validasi data materi untuk store/update.
     */
    private function validateMateriData(Request $request): array
    {
        $guru = $request->user()->guru;

        return $request->validate([
            'guru_kelas_id' => [
                'required',
                Rule::exists('guru_kelas', 'id')
                    ->where('guru_id', $guru->id)
                    ->where('aktif', true)
                    ->where('tahun_ajaran_id', $this->tahunAjaran?->id),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:10000'],
            'konten' => ['nullable', 'string', 'max:10000'],
            'file' => [
                'nullable',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip,mp4,mp3,txt',
            ],
        ], [], [
            'guru_kelas_id' => 'Kelas & Mata Pelajaran',
            'judul' => 'Judul Materi',
            'deskripsi' => 'Deskripsi',
            'konten' => 'Isi Materi',
            'file' => 'Berkas Materi',
        ]);
    }

    /**
     * Simpan materi baru beserta berkasnya.
     */
    public function store(Request $request): RedirectResponse
    {
        $guru = $request->user()->guru;

        $data = $this->validateMateriData($request);

        $file = $request->file('file');

        $materi = Materi::create([
            'guru_id' => $guru->id,
            'guru_kelas_id' => $data['guru_kelas_id'],
            'judul' => $data['judul'],
            'deskripsi' => HtmlSanitizer::clean($data['deskripsi'] ?? null),
            'konten' => HtmlSanitizer::clean($data['konten'] ?? null),
            'file_path' => $file?->store('materi', 'public'),
            'file_name' => $file?->getClientOriginalName(),
            'file_size' => $file?->getSize() ?? 0,
            'mime_type' => $file?->getMimeType(),
        ]);

        Toast::success('Materi "'.$materi->judul.'" berhasil diunggah.');

        return Redirect::back();
    }

    /**
     * Hapus materi beserta berkasnya.
     */
    public function destroy(Request $request, Materi $materi): RedirectResponse
    {
        if ($materi->guru_id !== $request->user()->guru?->id) {
            Toast::error('Materi tidak ditemukan.');

            return Redirect::back();
        }

        if ($materi->file_path) {
            Storage::disk('public')->delete($materi->file_path);
        }
        $materi->delete();

        Toast::success('Materi berhasil dihapus.');

        return Redirect::back();
    }

    /**
     * Unduh berkas materi milik guru.
     */
    public function unduh(Request $request, Materi $materi): StreamedResponse
    {
        abort_unless($materi->guru_id === $request->user()->guru?->id, 404);
        abort_unless($materi->file_path, 404);

        return Storage::disk('public')->download($materi->file_path, $materi->file_name);
    }
}
