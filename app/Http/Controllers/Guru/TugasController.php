<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\DetailPenilaian;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Penilaian;
use App\Models\SiswaKelas;
use App\Models\Tugas;
use App\Models\TugasPengumpulan;
use App\Support\HtmlSanitizer;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TugasController extends BaseAppController implements HasMiddleware
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
     * Daftar tugas milik guru beserta ringkasan pengumpulan.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('guru/Tugas/Index', $this->indexData($request));
    }

    /**
     * Jumlah siswa aktif per penugasan dalam satu query agregat.
     */
    private function jumlahSiswaPerPenugasan(iterable $guruKelasIds): array
    {
        return SiswaKelas::query()
            ->join('guru_kelas', 'guru_kelas.kelas_id', '=', 'siswa_kelas.kelas_id')
            ->whereIn('guru_kelas.id', $guruKelasIds)
            ->where('siswa_kelas.active', true)
            ->whereColumn('siswa_kelas.tahun_ajaran_id', 'guru_kelas.tahun_ajaran_id')
            ->selectRaw('guru_kelas.id as guru_kelas_id, count(*) as total')
            ->groupBy('guru_kelas.id')
            ->pluck('total', 'guru_kelas_id')
            ->all();
    }

    /**
     * Penugasan aktif guru untuk dropdown form.
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
     * Data tugas milik guru untuk modal edit.
     */
    public function edit(Request $request, Tugas $tugas): Response
    {
        abort_unless($tugas->guru_id === $request->user()->guru?->id, 404);

        return Inertia::render('guru/Tugas/Index', $this->indexData($request) + [
            'editTugas' => [
                'id' => $tugas->id,
                'guru_kelas_id' => $tugas->guru_kelas_id,
                'judul' => $tugas->judul,
                'deskripsi' => $tugas->deskripsi,
                'tanggal_terbit' => $tugas->tanggal_terbit?->format('Y-m-d H:i'),
                'deadline' => $tugas->deadline?->format('Y-m-d H:i'),
                'jenis_pengumpulan' => $tugas->jenis_pengumpulan,
                'poin' => $tugas->poin,
                'file_name' => $tugas->file_name,
                'file_size' => $tugas->file_size,
            ],
        ]);
    }

    /**
     * Simpan tugas baru beserta berkasnya (opsional).
     */
    public function store(Request $request): RedirectResponse
    {
        $guru = $request->user()->guru;

        $data = $this->validateTugasData($request);
        $file = $request->file('file');

        $tugas = Tugas::create([
            'guru_id' => $guru->id,
            'guru_kelas_id' => $data['guru_kelas_id'],
            'judul' => $data['judul'],
            'deskripsi' => HtmlSanitizer::clean($data['deskripsi'] ?? null),
            'tanggal_terbit' => $data['tanggal_terbit'] ?? now(),
            'deadline' => $data['deadline'],
            'jenis_pengumpulan' => $data['jenis_pengumpulan'] ?? 'file',
            'file_path' => $file?->store('tugas', 'public'),
            'file_name' => $file?->getClientOriginalName(),
            'file_size' => $file?->getSize() ?? 0,
            'mime_type' => $file?->getMimeType(),
            'poin' => $data['poin'] ?? 100,
        ]);

        $penilaian = Penilaian::create([
            'nama' => 'Tugas: '.$tugas->judul,
            'deskripsi' => HtmlSanitizer::clean($data['deskripsi'] ?? null),
            'tipe' => 'tugas',
            'nilai_maks' => $tugas->poin,
            'bobot' => 1,
            'aktif' => true,
            'sumber' => 'tugas',
        ]);

        $tugas->penilaian_id = $penilaian->id;
        $tugas->save();

        Toast::success('Tugas "'.$tugas->judul.'" berhasil dibuat.');

        return Redirect::back();
    }

    /**
     * Perbarui tugas milik guru; berkas diganti hanya jika diunggah ulang.
     */
    public function update(Request $request, Tugas $tugas): RedirectResponse
    {
        if ($tugas->guru_id !== $request->user()->guru?->id) {
            Toast::error('Tugas tidak ditemukan.');

            return Redirect::back();
        }

        $data = $this->validateTugasData($request);
        $file = $request->file('file');

        if ($file) {
            if ($tugas->file_path) {
                Storage::disk('public')->delete($tugas->file_path);
            }

            $tugas->file_path = $file->store('tugas', 'public');
            $tugas->file_name = $file->getClientOriginalName();
            $tugas->file_size = $file->getSize();
            $tugas->mime_type = $file->getMimeType();
        }

        $tugas->fill([
            'guru_kelas_id' => $data['guru_kelas_id'],
            'judul' => $data['judul'],
            'deskripsi' => HtmlSanitizer::clean($data['deskripsi'] ?? null),
            'tanggal_terbit' => $data['tanggal_terbit'] ?? now(),
            'deadline' => $data['deadline'],
            'jenis_pengumpulan' => $data['jenis_pengumpulan'] ?? $tugas->jenis_pengumpulan,
            'poin' => $data['poin'] ?? $tugas->poin,
        ])->save();

        if ($tugas->penilaian_id) {
            $tugas->penilaian()->update([
                'nama' => 'Tugas: '.$tugas->judul,
                'nilai_maks' => $tugas->poin,
            ]);
        }

        Toast::success('Tugas "'.$tugas->judul.'" berhasil diperbarui.');

        return Redirect::back();
    }

    /**
     * Hapus tugas beserta berkasnya dan semua pengumpulan.
     */
    public function destroy(Request $request, Tugas $tugas): RedirectResponse
    {
        if ($tugas->guru_id !== $request->user()->guru?->id) {
            Toast::error('Tugas tidak ditemukan.');

            return Redirect::back();
        }

        if ($tugas->file_path) {
            Storage::disk('public')->delete($tugas->file_path);
        }

        TugasPengumpulan::where('tugas_id', $tugas->id)
            ->pluck('file_path')
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        if ($tugas->penilaian_id) {
            Penilaian::where('id', $tugas->penilaian_id)->delete();
        }

        $tugas->delete();

        Toast::success('Tugas berhasil dihapus.');

        return Redirect::back();
    }

    /**
     * Daftar pengumpulan siswa untuk satu tugas.
     */
    public function pengumpulan(Request $request, Tugas $tugas): Response
    {
        abort_unless($tugas->guru_id === $request->user()->guru?->id, 404);

        $tugas->load('pengumpulans:id,tugas_id,siswa_nisn,file_name,jawaban_teks,submitted_at,nilai');

        $guruKelas = $tugas->guruKelas;

        $siswas = SiswaKelas::query()
            ->with(['siswa.user'])
            ->where('kelas_id', $guruKelas->kelas_id)
            ->where('tahun_ajaran_id', $guruKelas->tahun_ajaran_id)
            ->where('active', true)
            ->orderBy('siswa_nisn')
            ->get()
            ->map(function (SiswaKelas $sk) use ($tugas) {
                $pengumpulan = $tugas->pengumpulans->firstWhere('siswa_nisn', $sk->siswa_nisn);

                return [
                    'nisn' => $sk->siswa_nisn,
                    'nama' => $sk->siswa?->nama_lengkap ?? 'Siswa',
                    'file_name' => $pengumpulan?->file_name,
                    'jawaban_teks' => $pengumpulan?->jawaban_teks,
                    'submitted_at' => $pengumpulan?->submitted_at?->translatedFormat('d M Y H:i'),
                    'terlambat' => $pengumpulan !== null
                        && $tugas->deadline !== null
                        && $pengumpulan->submitted_at->gt($tugas->deadline),
                    'nilai' => $pengumpulan?->nilai,
                    'pengumpulan_id' => $pengumpulan?->id,
                ];
            });

        return Inertia::render('guru/Tugas/Pengumpulan', [
            'tugas' => [
                'id' => $tugas->id,
                'judul' => $tugas->judul,
                'kelas' => $guruKelas->kelas?->nama,
                'matpel' => $guruKelas->matpel?->name,
                'deadline' => $tugas->deadline?->translatedFormat('d M Y H:i'),
                'jenis_pengumpulan' => $tugas->jenis_pengumpulan,
                'poin' => $tugas->poin,
                'jumlah_siswa' => $siswas->count(),
            ],
            'siswas' => $siswas,
        ]);
    }

    /**
     * Simpan nilai satu pengumpulan siswa + sinkronisasi ke detail_penilaian.
     */
    public function nilai(Request $request, Tugas $tugas): RedirectResponse
    {
        abort_unless($tugas->guru_id === $request->user()->guru?->id, 404);

        $data = $request->validate([
            'siswa_nisn' => ['required', 'string', 'exists:siswa,nisn'],
            'nilai' => ['required', 'numeric', 'min:0', 'max:'.$tugas->poin],
        ]);

        $pengumpulan = TugasPengumpulan::where('tugas_id', $tugas->id)
            ->where('siswa_nisn', $data['siswa_nisn'])
            ->first();

        if (! $pengumpulan) {
            Toast::error('Siswa belum mengumpulkan tugas.');

            return Redirect::back();
        }

        $pengumpulan->update(['nilai' => $data['nilai']]);

        if ($tugas->penilaian_id) {
            DetailPenilaian::updateOrCreate(
                [
                    'penilaian_id' => $tugas->penilaian_id,
                    'guru_kelas_id' => $tugas->guru_kelas_id,
                    'siswa_nisn' => $data['siswa_nisn'],
                ],
                [
                    'tahun_ajaran_id' => $tugas->guruKelas->tahun_ajaran_id,
                    'guru_id' => $tugas->guru_id,
                    'nilai' => $data['nilai'],
                    'sumber' => 'tugas',
                ]
            );
        }

        Toast::success('Nilai berhasil disimpan.');

        return Redirect::back();
    }

    /**
     * Unduh berkas pengumpulan siswa (hanya guru pengampu).
     */
    public function pengumpulanUnduh(Request $request, Tugas $tugas, TugasPengumpulan $pengumpulan): StreamedResponse
    {
        abort_unless($tugas->guru_id === $request->user()->guru?->id, 404);
        abort_unless($pengumpulan->tugas_id === $tugas->id, 404);
        abort_unless($pengumpulan->file_path, 404);

        return Storage::disk('public')->download($pengumpulan->file_path, $pengumpulan->file_name);
    }

    /**
     * Unduh berkas tugas milik guru.
     */
    public function unduh(Request $request, Tugas $tugas): StreamedResponse
    {
        abort_unless($tugas->guru_id === $request->user()->guru?->id, 404);
        abort_unless($tugas->file_path, 404);

        return Storage::disk('public')->download($tugas->file_path, $tugas->file_name);
    }

    /**
     * Data dasar halaman guru tugas (dipakai index & edit).
     */
    private function indexData(Request $request): array
    {
        $guru = $request->user()->guru;

        $tugases = Tugas::query()
            ->select(['id', 'guru_kelas_id', 'judul', 'deskripsi', 'tanggal_terbit', 'deadline', 'jenis_pengumpulan', 'file_name', 'file_size', 'created_at'])
            ->with(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name'])
            ->withCount('pengumpulans')
            ->withCount(['pengumpulans as jumlah_terlambat' => fn ($q) => $q->whereColumn('tugas_pengumpulans.submitted_at', '>', 'tugases.deadline')])
            ->where('guru_id', $guru->id)
            ->whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $this->tahunAjaran?->id))
            ->when($request->integer('guru_kelas_id'), fn ($q, $gkId) => $q->where('guru_kelas_id', $gkId))
            ->when($request->string('q')->toString(), fn ($q, $keyword) => $q->where(fn ($w) => $w
                ->where('judul', 'like', "%{$keyword}%")
                ->orWhere('deskripsi', 'like', "%{$keyword}%")))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $jumlahSiswaPerPenugasan = $this->jumlahSiswaPerPenugasan(
            $tugases->getCollection()->pluck('guru_kelas_id')->unique(),
        );

        $tugases->through(fn (Tugas $tugas): array => [
            'id' => $tugas->id,
            'judul' => $tugas->judul,
            'deskripsi' => $tugas->deskripsi,
            'file_name' => $tugas->file_name,
            'file_size' => $tugas->file_size,
            'jenis_pengumpulan' => $tugas->jenis_pengumpulan,
            'kelas' => $tugas->guruKelas?->kelas?->nama,
            'matpel' => $tugas->guruKelas?->matpel?->name,
            'tanggal_terbit' => $tugas->tanggal_terbit?->translatedFormat('d M Y H:i'),
            'deadline' => $tugas->deadline?->translatedFormat('d M Y H:i'),
            'deadline_at' => $tugas->deadline?->toIso8601String(),
            'sudah_terbit' => $tugas->sudahTerbit(),
            'jumlah_siswa' => $jumlahSiswaPerPenugasan[$tugas->guru_kelas_id] ?? 0,
            'jumlah_kumpul' => $tugas->pengumpulans_count,
            'jumlah_terlambat' => $tugas->jumlah_terlambat,
            'dibuat_pada' => $tugas->created_at?->translatedFormat('d M Y H:i'),
        ]);

        return [
            'tugases' => $tugases,
            'penugasan' => $this->penugasan($guru),
            'filters' => [
                'guru_kelas_id' => $request->integer('guru_kelas_id') ?: null,
                'q' => $request->string('q')->toString(),
            ],
        ];
    }

    /**
     * Validasi data tugas untuk store/update.
     */
    private function validateTugasData(Request $request): array
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
            'tanggal_terbit' => ['nullable', 'date'],
            'deadline' => ['required', 'date', 'after_or_equal:tanggal_terbit'],
            'jenis_pengumpulan' => ['sometimes', 'required', Rule::in(['file', 'teks', 'keduanya'])],
            'poin' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'file' => [
                'nullable',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip,mp4,mp3,txt',
            ],
        ], [], [
            'guru_kelas_id' => 'Kelas & Mata Pelajaran',
            'judul' => 'Judul Tugas',
            'deskripsi' => 'Deskripsi',
            'tanggal_terbit' => 'Tanggal Terbit',
            'deadline' => 'Batas Waktu',
            'jenis_pengumpulan' => 'Cara Pengumpulan',
            'poin' => 'Poin',
            'file' => 'Berkas Tugas',
        ]);
    }
}
