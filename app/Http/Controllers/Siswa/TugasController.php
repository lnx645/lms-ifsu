<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\BaseAppController;
use App\Models\DetailPenilaian;
use App\Models\GuruKelas;
use App\Models\SiswaKelas;
use App\Models\Tugas;
use App\Models\TugasPengumpulan;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TugasController extends BaseAppController implements HasMiddleware
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
     * Daftar tugas terbit untuk kelas siswa beserta status pengumpulan.
     */
    public function index(Request $request): Response
    {
        $siswa = $request->user()->siswa;

        $guruKelasIds = $this->guruKelasIdsSiswa($siswa->nisn);

        $tugases = Tugas::query()
            ->select(['id', 'guru_kelas_id', 'judul', 'deskripsi', 'tanggal_terbit', 'deadline', 'jenis_pengumpulan', 'file_name', 'file_size', 'poin'])
            ->with(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'guruKelas.guru:id,nama_lengkap'])
            ->with(['pengumpulans' => fn ($q) => $q->where('siswa_nisn', $siswa->nisn)->select(['id', 'tugas_id', 'file_name', 'submitted_at', 'nilai'])])
            ->whereIn('guru_kelas_id', $guruKelasIds)
            ->where(fn ($q) => $q
                ->whereNull('tanggal_terbit')
                ->orWhere('tanggal_terbit', '<=', now()))
            ->orderBy('deadline')
            ->get()
            ->map(function (Tugas $tugas) {
                $pengumpulan = $tugas->pengumpulans->first();

                return [
                    'id' => $tugas->id,
                    'judul' => $tugas->judul,
                    'kelas' => $tugas->guruKelas?->kelas?->nama,
                    'matpel' => $tugas->guruKelas?->matpel?->name,
                    'guru' => $tugas->guruKelas?->guru?->nama_lengkap,
                    'tanggal_terbit' => $tugas->tanggal_terbit?->translatedFormat('d M Y H:i'),
                    'deadline' => $tugas->deadline?->translatedFormat('d M Y H:i'),
                    'deadline_at' => $tugas->deadline?->toIso8601String(),
                    'jenis_pengumpulan' => $tugas->jenis_pengumpulan,
                    'file_name' => $tugas->file_name,
                    'poin' => $tugas->poin,
                    'nilai' => $pengumpulan?->nilai,
                    'status' => $this->statusUntukSiswa($tugas, $pengumpulan),
                    'submitted_at' => $pengumpulan?->submitted_at?->translatedFormat('d M Y H:i'),
                ];
            });

        return Inertia::render('siswa/Tugas/Index', ['tugases' => $tugases]);
    }

    /**
     * Detail tugas + status pengumpulan siswa.
     */
    public function show(Request $request, Tugas $tugas): Response
    {
        $siswa = $request->user()->siswa;

        abort_unless($this->guruKelasIdsSiswa($siswa->nisn)->contains($tugas->guru_kelas_id), 404);
        abort_unless($tugas->sudahTerbit(), 404);

        $tugas->load(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'guruKelas.guru:id,nama_lengkap']);

        $pengumpulan = TugasPengumpulan::where('tugas_id', $tugas->id)
            ->where('siswa_nisn', $siswa->nisn)
            ->first();

        return Inertia::render('siswa/Tugas/Detail', [
            'tugas' => [
                'id' => $tugas->id,
                'judul' => $tugas->judul,
                'deskripsi' => $tugas->deskripsi,
                'kelas' => $tugas->guruKelas?->kelas?->nama,
                'matpel' => $tugas->guruKelas?->matpel?->name,
                'guru' => $tugas->guruKelas?->guru?->nama_lengkap,
                'tanggal_terbit' => $tugas->tanggal_terbit?->translatedFormat('d M Y H:i'),
                'deadline' => $tugas->deadline?->translatedFormat('d M Y H:i'),
                'deadline_at' => $tugas->deadline?->toIso8601String(),
                'jenis_pengumpulan' => $tugas->jenis_pengumpulan,
                'file_name' => $tugas->file_name,
                'file_size' => $tugas->file_size,
                'mime_type' => $tugas->mime_type,
                'poin' => $tugas->poin,
                'status' => $this->statusUntukSiswa($tugas, $pengumpulan),
            ],
            'pengumpulan' => $pengumpulan ? [
                'id' => $pengumpulan->id,
                'file_name' => $pengumpulan->file_name,
                'file_size' => $pengumpulan->file_size,
                'jawaban_teks' => $pengumpulan->jawaban_teks,
                'submitted_at' => $pengumpulan->submitted_at?->translatedFormat('d M Y H:i'),
                'terlambat' => $tugas->deadline !== null && $pengumpulan->submitted_at->gt($tugas->deadline),
                'nilai' => $pengumpulan->nilai,
            ] : null,
        ]);
    }

    /**
     * Kumpulkan (atau ganti) jawaban tugas. Batas waktu memakai pengumpulan pertama.
     */
    public function kumpul(Request $request, Tugas $tugas): RedirectResponse
    {
        $siswa = $request->user()->siswa;

        abort_unless($this->guruKelasIdsSiswa($siswa->nisn)->contains($tugas->guru_kelas_id), 404);
        abort_unless($tugas->sudahTerbit(), 404);

        if ($tugas->sudahLewatDeadline()) {
            Toast::error('Batas waktu pengumpulan sudah lewat.');

            return Redirect::back();
        }

        $jenis = $tugas->jenis_pengumpulan;

        $rules = [
            'jawaban_teks' => ['nullable', 'string', 'max:10000'],
            'file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip,mp4,mp3,txt'],
        ];

        if ($jenis === 'file') {
            $rules['file'][] = 'required';
            $rules['jawaban_teks'][] = 'prohibited';
        } elseif ($jenis === 'teks') {
            $rules['jawaban_teks'][] = 'required';
            $rules['file'][] = 'prohibited';
        } else {
            $rules['file'][] = 'required_without:jawaban_teks';
            $rules['jawaban_teks'][] = 'required_without:file';
        }

        $data = $request->validate($rules, [], [
            'jawaban_teks' => 'Jawaban',
            'file' => 'Berkas Jawaban',
        ]);

        $file = $request->file('file');

        $pengumpulan = TugasPengumpulan::where('tugas_id', $tugas->id)
            ->where('siswa_nisn', $siswa->nisn)
            ->first();

        if ($pengumpulan) {
            if ($pengumpulan->nilai !== null) {
                $pengumpulan->nilai = null;
                if ($tugas->penilaian_id) {
                    DetailPenilaian::where('penilaian_id', $tugas->penilaian_id)
                        ->where('siswa_nisn', $siswa->nisn)
                        ->delete();
                }
            }
            if ($file) {
                if ($pengumpulan->file_path) {
                    Storage::disk('public')->delete($pengumpulan->file_path);
                }
                $pengumpulan->file_path = $file->store('tugas-kumpul', 'public');
                $pengumpulan->file_name = $file->getClientOriginalName();
                $pengumpulan->file_size = $file->getSize();
                $pengumpulan->mime_type = $file->getMimeType();
            }
            $pengumpulan->jawaban_teks = $data['jawaban_teks'] ?? null;
            $pengumpulan->save();
            Toast::success('Jawaban tugas diperbarui.');
        } else {
            TugasPengumpulan::create([
                'tugas_id' => $tugas->id,
                'siswa_nisn' => $siswa->nisn,
                'file_path' => $file?->store('tugas-kumpul', 'public'),
                'file_name' => $file?->getClientOriginalName(),
                'file_size' => $file?->getSize() ?? 0,
                'mime_type' => $file?->getMimeType(),
                'jawaban_teks' => $data['jawaban_teks'] ?? null,
                'submitted_at' => now(),
            ]);
            Toast::success('Tugas berhasil dikumpulkan.');
        }

        return Redirect::back();
    }

    /**
     * Unduh berkas tugas kelas siswa.
     */
    public function unduh(Request $request, Tugas $tugas): StreamedResponse
    {
        $siswa = $request->user()->siswa;

        abort_unless($this->guruKelasIdsSiswa($siswa->nisn)->contains($tugas->guru_kelas_id), 404);
        abort_unless($tugas->sudahTerbit(), 404);
        abort_unless($tugas->file_path, 404);

        return Storage::disk('public')->download($tugas->file_path, $tugas->file_name);
    }

    /**
     * Status pengumpulan dari sudut pandang siswa.
     */
    private function statusUntukSiswa(Tugas $tugas, ?TugasPengumpulan $pengumpulan): string
    {
        if ($pengumpulan) {
            return $tugas->deadline !== null && $pengumpulan->submitted_at->gt($tugas->deadline)
                ? 'terlambat'
                : 'terkumpul';
        }

        return $tugas->sudahLewatDeadline() ? 'tutup' : 'belum';
    }

    /**
     * Penugasan kelas aktif siswa di tahun ajaran berjalan.
     */
    private function guruKelasIdsSiswa(string $nisn): Collection
    {
        $siswaKelas = SiswaKelas::where('siswa_nisn', $nisn)
            ->where('active', true)
            ->when($this->tahunAjaran, fn ($q, $ta) => $q->where('tahun_ajaran_id', $ta->id))
            ->first();

        if (! $siswaKelas) {
            return collect();
        }

        return GuruKelas::where('kelas_id', $siswaKelas->kelas_id)
            ->where('aktif', true)
            ->where('tahun_ajaran_id', $siswaKelas->tahun_ajaran_id)
            ->pluck('id');
    }
}
