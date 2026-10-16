<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\DetailPenilaian;
use App\Models\JawabanSiswa;
use App\Models\Penilaian;
use App\Models\SiswaKelas;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\UjianService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class HasilUjianController extends BaseAppController implements HasMiddleware
{
    public function __construct(private readonly UjianService $ujianService)
    {
        parent::__construct();
    }

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
     * Rekap pengerjaan seluruh siswa untuk satu ujian.
     */
    public function index(Request $request, Ujian $ujian): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        return Inertia::render('guru/Ujian/Hasil', $this->hasilData($ujian));
    }

    /**
     * Detail satu sesi pengerjaan untuk koreksi esai & lihat pelanggaran.
     */
    public function show(Request $request, Ujian $ujian, UjianPengerjaan $pengerjaan): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($pengerjaan->ujian_id === $ujian->id, 404);

        // Hanya load relasi yang dipakai: jawabans untuk keyBy, soals+opsi untuk daftar lengkap.
        // (soal.opsi tidak perlu via jawabans lagi karena diambil dari $ujian->soals)
        $pengerjaan->load(['jawabans', 'pelanggarans', 'siswa']);
        $ujian->load(['soals.opsi']);

        // Petakan jawaban per soal_id untuk lookup cepat.
        $jawabanMap = $pengerjaan->jawabans->keyBy('soal_id');

        // Tampilkan SEMUA soal ujian, bukan cuma yang dijawab.
        // Soal yang tidak dijawab tampil dengan status "Tidak dijawab" dan skor 0.
        $semuaJawaban = $ujian->soals->map(function ($soal) use ($jawabanMap): array {
            $j = $jawabanMap->get($soal->id);

            return [
                'id' => $j?->id,
                'soal_id' => $soal->id,
                'tipe' => $soal->tipe,
                'pertanyaan' => $soal->pertanyaan,
                'poin' => $soal->poin,
                'opsi_dipilih' => $j?->opsi_dipilih,
                'jawaban_teks' => $j?->jawaban_teks,
                'benar' => $j?->benar,
                'skor' => $j?->skor ?? 0,
                'dijawab' => $j !== null,
                'opsi' => $soal->opsi->map(fn ($o): array => [
                    'id' => $o->id,
                    'teks' => $o->teks,
                    'benar' => $o->benar,
                ])->values(),
                'detail' => $soal->detail,
            ];
        })->values();

        return Inertia::render('guru/Ujian/Koreksi', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'nilai_maks' => $ujian->nilai_maks,
            ],
            'pengerjaan' => [
                'id' => $pengerjaan->id,
                'siswa' => $pengerjaan->siswa?->nama_lengkap ?? $pengerjaan->siswa_nisn,
                'nisn' => $pengerjaan->siswa_nisn,
                'status' => $pengerjaan->status,
                'nilai_objektif' => $pengerjaan->nilai_objektif,
                'nilai_esai' => $pengerjaan->nilai_esai,
                'nilai_total' => $pengerjaan->nilai_total,
                'jumlah_pelanggaran' => $pengerjaan->jumlah_pelanggaran,
                'submitted_at' => $pengerjaan->submitted_at?->translatedFormat('d M Y H:i'),
            ],
            'jawabans' => $semuaJawaban,
            'pelanggarans' => $pengerjaan->pelanggarans->map(fn ($p): array => [
                'jenis' => $p->jenis,
                'terjadi_at' => $p->terjadi_at?->translatedFormat('d M Y H:i:s'),
            ])->values(),
        ]);
    }

    /**
     * Simpan skor esai satu jawaban lalu hitung ulang nilai.
     */
    public function nilaiEsai(Request $request, Ujian $ujian, UjianPengerjaan $pengerjaan, JawabanSiswa $jawaban): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($pengerjaan->ujian_id === $ujian->id, 404);
        abort_unless($jawaban->ujian_pengerjaan_id === $pengerjaan->id, 404);
        abort_unless($jawaban->soal?->esai(), 404);

        $data = $request->validate([
            'skor' => ['required', 'numeric', 'min:0', 'max:'.$jawaban->soal->poin],
        ]);

        $jawaban->update(['skor' => $data['skor'], 'benar' => null]);

        $this->ujianService->rekalkulasiNilai($pengerjaan->fresh());

        Toast::success('Skor esai disimpan.');

        return Redirect::back();
    }

    /**
     * Tarik nilai CBT ke sistem Penilaian formal.
     * Membuat record Penilaian + DetailPenilaian dari nilai_total tiap siswa.
     */
    public function tarikKeNilai(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $ujian->load(['guruKelas', 'pengerjaans']);
        $guruKelas = $ujian->guruKelas;
        abort_unless($guruKelas, 404, 'Ujian belum terhubung ke kelas.');

        // Ambil pengerjaan terakhir tiap siswa yang sudah submit
        $pengerjaanByNisn = $ujian->pengerjaans
            ->where('status', 'selesai')
            ->sortByDesc('attempt_ke')
            ->unique('siswa_nisn');

        if ($pengerjaanByNisn->isEmpty()) {
            Toast::error('Belum ada siswa yang menyelesaikan ujian.');

            return Redirect::back();
        }

        // Buat atau ambil Penilaian untuk ujian ini
        // Standar akademik sempurna: terikat langsung via ujian_id (1 ujian = 1 penilaian)
        // Tidak bergantung pada nama teks, judul diganti pun tetap 1 record
        $tahunAjaranId = $guruKelas->tahun_ajaran_id;
        $penilaian = Penilaian::firstOrCreate(
            ['ujian_id' => $ujian->id],
            [
                'nama' => "CBT: {$ujian->judul}",
                'tahun_ajaran_id' => $tahunAjaranId,
                'deskripsi' => "Nilai otomatis dari ujian CBT '{$ujian->judul}' ({$ujian->kategori})",
                'tipe' => 'cbt',
                'nilai_maks' => 100,
                'bobot' => 1,
                'aktif' => true,
                'semester' => null,
            ]
        );

        // Update nama jika judul ujian berubah (tetap 1 record)
        if ($penilaian->nama !== "CBT: {$ujian->judul}") {
            $penilaian->update(['nama' => "CBT: {$ujian->judul}"]);
        }

        // Hubungkan ke kelas
        $penilaian->kelas()->syncWithoutDetaching([$guruKelas->kelas_id]);

        $jumlah = 0;
        foreach ($pengerjaanByNisn as $p) {
            DetailPenilaian::updateOrCreate(
                [
                    'penilaian_id' => $penilaian->id,
                    'siswa_nisn' => $p->siswa_nisn,
                ],
                [
                    'guru_id' => $request->user()->guru->id,
                    'guru_kelas_id' => $ujian->guru_kelas_id,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'nilai' => $p->nilai_total ?? 0,
                    'sumber' => 'cbt',
                    'keterangan' => "Dari ujian '{$ujian->judul}'",
                ]
            );
            $jumlah++;
        }

        Toast::success("Nilai {$jumlah} siswa ditarik ke Penilaian.");

        return Redirect::back();
    }

    /**
     * @return array<string, mixed>
     */
    private function hasilData(Ujian $ujian): array
    {
        $ujian->load(['guruKelas']);
        $guruKelas = $ujian->guruKelas;

        // Optimasi: ambil hanya attempt terbaru per siswa via subquery (bukan filter di PHP)
        $latestIds = DB::table('cbt_ujian_pengerjaans')
            ->selectRaw('MAX(id) as id')
            ->where('ujian_id', $ujian->id)
            ->groupBy('siswa_nisn');
        $pengerjaanByNisn = UjianPengerjaan::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('siswa_nisn');

        $siswas = SiswaKelas::query()
            ->with('siswa')
            ->where('kelas_id', $guruKelas->kelas_id)
            ->where('tahun_ajaran_id', $guruKelas->tahun_ajaran_id)
            ->where('active', true)
            ->orderBy('siswa_nisn')
            ->get()
            ->map(function (SiswaKelas $sk) use ($pengerjaanByNisn): array {
                $p = $pengerjaanByNisn->get($sk->siswa_nisn);

                return [
                    'nisn' => $sk->siswa_nisn,
                    'nama' => $sk->siswa?->nama_lengkap ?? 'Siswa',
                    'pengerjaan_id' => $p?->id,
                    'status' => $p?->status ?? 'belum',
                    'nilai_total' => $p?->nilai_total,
                    'nilai_objektif' => $p?->nilai_objektif,
                    'nilai_esai' => $p?->nilai_esai,
                    'jumlah_pelanggaran' => $p?->jumlah_pelanggaran ?? 0,
                    'submitted_at' => $p?->submitted_at?->translatedFormat('d M Y H:i'),
                ];
            });

        return [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'nilai_maks' => $ujian->nilai_maks,
                'kelas' => $guruKelas->kelas?->nama,
                'matpel' => $guruKelas->matpel?->name,
                'jumlah_soal' => $ujian->soals()->count(),
                'ada_esai' => $ujian->soals()->where('tipe', 'esai')->exists(),
            ],
            'siswas' => $siswas,
        ];
    }
}
