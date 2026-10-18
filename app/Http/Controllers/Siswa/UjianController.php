<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\BaseAppController;
use App\Models\JawabanSiswa;
use App\Models\OpsiSoal;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ujian untuk siswa — dibangun ulang 2026-10-18 untuk KP.
 * Alur: daftar → konfirmasi → kerjakan → kumpulkan → hasil.
 */
class UjianController extends BaseAppController implements HasMiddleware
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
     * Daftar ujian terbit untuk kelas siswa.
     */
    public function index(Request $request): Response
    {
        $siswa = $request->user()->siswa;
        $guruKelasIds = $this->guruKelasIdsSiswa($siswa->nisn);

        $ujians = Ujian::query()
            ->with(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name'])
            ->withCount('soals')
            ->where('status', 'terbit')
            ->whereIn('guru_kelas_id', $guruKelasIds)
            ->orderByDesc('created_at')
            ->get();

        $pengerjaans = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->whereIn('ujian_id', $ujians->pluck('id'))
            ->get()
            ->groupBy('ujian_id');

        return Inertia::render('siswa/Ujian/Index', [
            'ujians' => $ujians->map(function (Ujian $u) use ($pengerjaans): array {
                $riwayat = $pengerjaans->get($u->id, collect());
                $selesai = $riwayat->whereIn('status', ['selesai', 'auto_submit']);
                $berlangsung = $riwayat->firstWhere('status', 'berlangsung');

                return [
                    'id' => $u->id,
                    'judul' => $u->judul,
                    'kategori' => $u->kategori,
                    'matpel' => $u->guruKelas?->matpel?->name,
                    'kelas' => $u->guruKelas?->kelas?->nama,
                    'durasi_menit' => $u->durasi_menit,
                    'jumlah_soal' => $u->soals_count,
                    'butuh_token' => $u->token !== null,
                    'attempt_terpakai' => $selesai->count(),
                    'maks_attempt' => $u->maks_attempt,
                    'sisa_attempt' => max(0, $u->maks_attempt - $selesai->count()),
                    'sedang_dikerjakan' => $berlangsung !== null,
                    'tampilkan_hasil' => (bool) $u->tampilkan_hasil,
                    'nilai_terbaik' => $u->tampilkan_hasil ? $selesai->max('nilai_total') : null,
                ];
            }),
        ]);
    }

    /**
     * Halaman konfirmasi — tampilkan info ujian + tata tertib.
     */
    public function konfirmasi(Request $request, Ujian $ujian): Response
    {
        $siswa = $request->user()->siswa;
        abort_unless($this->guruKelasIdsSiswa($siswa->nisn)->contains($ujian->guru_kelas_id), 404);
        abort_unless($ujian->status === 'terbit', 404);

        $ujian->loadCount('soals');
        $ujian->load(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name']);

        $sudahSelesai = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->where('ujian_id', $ujian->id)
            ->whereIn('status', ['selesai', 'auto_submit'])
            ->count();

        $berlangsung = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->where('ujian_id', $ujian->id)
            ->where('status', 'berlangsung')
            ->first();

        return Inertia::render('siswa/Ujian/Konfirmasi', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'deskripsi' => $ujian->deskripsi,
                'tata_tertib' => $ujian->tata_tertib,
                'kategori' => $ujian->kategori,
                'matpel' => $ujian->guruKelas?->matpel?->name,
                'kelas' => $ujian->guruKelas?->kelas?->nama,
                'durasi_menit' => $ujian->durasi_menit,
                'jumlah_soal' => $ujian->soals_count,
                'butuh_token' => $ujian->token !== null,
                'acak_soal' => (bool) $ujian->acak_soal,
            ],
            'sisa_attempt' => max(0, $ujian->maks_attempt - $sudahSelesai),
            'sedang_dikerjakan' => $berlangsung !== null,
            'pengerjaan_id' => $berlangsung?->id,
        ]);
    }

    /**
     * Mulai ujian — buat pengerjaan baru.
     */
    public function mulai(Request $request, Ujian $ujian): RedirectResponse
    {
        $siswa = $request->user()->siswa;
        abort_unless($this->guruKelasIdsSiswa($siswa->nisn)->contains($ujian->guru_kelas_id), 404);
        abort_unless($ujian->status === 'terbit', 404);

        // Cek token jika dibutuhkan
        if ($ujian->token !== null) {
            $request->validate(['token' => 'required|string']);
            if ($request->input('token') !== $ujian->token) {
                return back()->withErrors(['token' => 'Token salah.']);
            }
        }

        // Cek sisa attempt
        $sudahSelesai = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->where('ujian_id', $ujian->id)
            ->whereIn('status', ['selesai', 'auto_submit'])
            ->count();

        if ($sudahSelesai >= $ujian->maks_attempt) {
            return back()->withErrors(['ujian' => 'Kesempatan mengerjakan sudah habis.']);
        }

        // Lanjutkan yang sedang berlangsung jika ada
        $berlangsung = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->where('ujian_id', $ujian->id)
            ->where('status', 'berlangsung')
            ->first();

        if ($berlangsung === null) {
            $berlangsung = UjianPengerjaan::create([
                'ujian_id' => $ujian->id,
                'siswa_nisn' => $siswa->nisn,
                'attempt_ke' => $sudahSelesai + 1,
                'mulai_at' => now(),
                'batas_at' => now()->addMinutes($ujian->durasi_menit),
                'status' => 'berlangsung',
            ]);
        }

        return redirect()->route('siswa.ujian.kerjakan', $ujian->id);
    }

    /**
     * Halaman kerjakan soal.
     */
    public function kerjakan(Request $request, Ujian $ujian): Response
    {
        $siswa = $request->user()->siswa;
        abort_unless($this->guruKelasIdsSiswa($siswa->nisn)->contains($ujian->guru_kelas_id), 404);

        $pengerjaan = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->where('ujian_id', $ujian->id)
            ->where('status', 'berlangsung')
            ->firstOrFail();

        // Auto-submit jika waktu habis
        if (now()->greaterThan($pengerjaan->batas_at)) {
            $this->nilaiOtomatis($pengerjaan);
            return redirect()->route('siswa.ujian.hasil', $ujian->id);
        }

        $soals = Soal::query()
            ->where('ujian_id', $ujian->id)
            ->with(['opsis' => fn ($q) => $q->orderBy('urutan')])
            ->orderBy('urutan')
            ->get();

        // Acak soal jika diset
        if ($ujian->acak_soal) {
            $soals = $soals->shuffle();
        }

        // Acak opsi jika diset
        if ($ujian->acak_opsi) {
            $soals->each(fn ($s) => $s->setRelation('opsis', $s->opsis->shuffle()->values()));
        }

        // Jawaban yang sudah disimpan (tanpa kunci jawaban)
        $jawabanTersimpan = JawabanSiswa::query()
            ->where('ujian_pengerjaan_id', $pengerjaan->id)
            ->get()
            ->keyBy('soal_id');

        return Inertia::render('siswa/Ujian/Kerjakan', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'durasi_menit' => $ujian->durasi_menit,
            ],
            'pengerjaan' => [
                'id' => $pengerjaan->id,
                'batas_at' => $pengerjaan->batas_at->toIso8601String(),
                'server_time' => now()->toIso8601String(),
            ],
            'soals' => $soals->map(function (Soal $s) use ($jawabanTersimpan): array {
                $tersimpan = $jawabanTersimpan->get($s->id);
                return [
                    'id' => $s->id,
                    'tipe' => $s->tipe,
                    'pertanyaan' => $s->pertanyaan,
                    'poin' => $s->poin,
                    'media_path' => $s->media_path,
                    'media_type' => $s->media_type,
                    'opsis' => $s->opsis->map(fn (OpsiSoal $o) => [
                        'id' => $o->id,
                        'teks' => $o->teks,
                    ])->values(),
                    'jawaban_tersimpan' => $tersimpan ? [
                        'opsi_dipilih' => $tersimpan->opsi_dipilih,
                        'jawaban_teks' => $tersimpan->jawaban_teks,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Simpan jawaban (AJAX).
     */
    public function simpanJawaban(Request $request, Ujian $ujian): JsonResponse
    {
        $siswa = $request->user()->siswa;

        $pengerjaan = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->where('ujian_id', $ujian->id)
            ->where('status', 'berlangsung')
            ->firstOrFail();

        if (now()->greaterThan($pengerjaan->batas_at)) {
            return response()->json(['error' => 'Waktu habis.'], 422);
        }

        $data = $request->validate([
            'soal_id' => 'required|integer|exists:cbt_soals,id',
            'opsi_dipilih' => 'nullable|array',
            'jawaban_teks' => 'nullable|string|max:5000',
        ]);

        // Pastikan soal milik ujian ini
        abort_unless(Soal::where('id', $data['soal_id'])->where('ujian_id', $ujian->id)->exists(), 404);

        JawabanSiswa::updateOrCreate(
            ['ujian_pengerjaan_id' => $pengerjaan->id, 'soal_id' => $data['soal_id']],
            [
                'opsi_dipilih' => $data['opsi_dipilih'] ?? null,
                'jawaban_teks' => $data['jawaban_teks'] ?? null,
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Kumpulkan ujian + penilaian otomatis.
     */
    public function submit(Request $request, Ujian $ujian): RedirectResponse
    {
        $siswa = $request->user()->siswa;

        $pengerjaan = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->where('ujian_id', $ujian->id)
            ->where('status', 'berlangsung')
            ->firstOrFail();

        $this->nilaiOtomatis($pengerjaan);

        return redirect()->route('siswa.ujian.hasil', $ujian->id);
    }

    /**
     * Halaman hasil/nilai.
     */
    public function hasil(Request $request, Ujian $ujian): Response
    {
        $siswa = $request->user()->siswa;
        abort_unless($this->guruKelasIdsSiswa($siswa->nisn)->contains($ujian->guru_kelas_id), 404);

        $pengerjaan = UjianPengerjaan::query()
            ->where('siswa_nisn', $siswa->nisn)
            ->where('ujian_id', $ujian->id)
            ->whereIn('status', ['selesai', 'auto_submit'])
            ->orderByDesc('submitted_at')
            ->firstOrFail();

        abort_unless($ujian->tampilkan_hasil, 403);

        return Inertia::render('siswa/Ujian/Hasil', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'nilai_maks' => $ujian->nilai_maks,
                'kkm' => $ujian->kkm,
            ],
            'hasil' => [
                'nilai_total' => $pengerjaan->nilai_total,
                'nilai_objektif' => $pengerjaan->nilai_objektif,
                'jumlah_benar' => $pengerjaan->jumlah_benar,
                'jumlah_salah' => $pengerjaan->jumlah_salah,
                'jumlah_kosong' => $pengerjaan->jumlah_kosong,
                'lulus_kkm' => $ujian->kkm ? $pengerjaan->nilai_total >= $ujian->kkm : null,
                'submitted_at' => $pengerjaan->submitted_at?->translatedFormat('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Penilaian otomatis untuk soal objektif (PG, benar/salah).
     */
    private function nilaiOtomatis(UjianPengerjaan $pengerjaan): void
    {
        DB::transaction(function () use ($pengerjaan): void {
            $ujian = $pengerjaan->ujian;
            $soals = Soal::query()
                ->where('ujian_id', $ujian->id)
                ->with('opsis')
                ->get()
                ->keyBy('id');

            $jawabanSiswa = JawabanSiswa::query()
                ->where('ujian_pengerjaan_id', $pengerjaan->id)
                ->get();

            $totalSkor = 0;
            $benar = 0;
            $salah = 0;
            $kosong = 0;

            foreach ($soals as $soal) {
                $jawaban = $jawabanSiswa->firstWhere('soal_id', $soal->id);

                if ($jawaban === null || ($jawaban->opsi_dipilih === null && $jawaban->jawaban_teks === null)) {
                    $kosong++;
                    continue;
                }

                $isBenar = false;
                $skor = 0;

                if (in_array($soal->tipe, ['pg', 'benar_salah', 'pg_kompleks'], true)) {
                    $kunciBenar = $soal->opsis->where('benar', true)->pluck('id')->sort()->values()->all();
                    $dipilih = collect($jawaban->opsi_dipilih ?? [])->sort()->values()->all();
                    $isBenar = $kunciBenar === $dipilih;
                } elseif ($soal->tipe === 'isian' && $soal->kunci_isian !== null) {
                    $teksJawaban = trim((string) $jawaban->jawaban_teks);
                    $kunci = trim($soal->kunci_isian);
                    $isBenar = $soal->isian_case_sensitive
                        ? $teksJawaban === $kunci
                        : mb_strtolower($teksJawaban) === mb_strtolower($kunci);
                }
                // Tipe esai: tidak dinilai otomatis, menunggu koreksi guru

                if ($isBenar) {
                    $skor = (float) $soal->poin;
                    $benar++;
                } else {
                    $salah++;
                }

                $totalSkor += $skor;

                if ($jawaban !== null) {
                    $jawaban->update(['benar' => $isBenar, 'skor' => $skor]);
                }
            }

            $nilaiMaks = (float) ($ujian->nilai_maks ?? 100);
            $totalPoin = (float) $soals->sum('poin');
            $nilaiTotal = $totalPoin > 0 ? round(($totalSkor / $totalPoin) * $nilaiMaks, 2) : 0;

            $pengerjaan->update([
                'submitted_at' => now(),
                'status' => now()->greaterThan($pengerjaan->batas_at) ? 'auto_submit' : 'selesai',
                'nilai_objektif' => $totalSkor,
                'nilai_total' => $nilaiTotal,
                'jumlah_benar' => $benar,
                'jumlah_salah' => $salah,
                'jumlah_kosong' => $kosong,
            ]);
        });

        $pengerjaan->refresh();
    }

    /**
     * Daftar guru_kelas_id untuk kelas-kelas siswa.
     */
    private function guruKelasIdsSiswa(string $nisn): Collection
    {
        $kelasIds = SiswaKelas::query()
            ->where('siswa_nisn', $nisn)
            ->pluck('kelas_id');

        return \App\Models\GuruKelas::query()
            ->whereIn('kelas_id', $kelasIds)
            ->pluck('id');
    }
}
