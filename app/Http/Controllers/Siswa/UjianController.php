<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\BaseAppController;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\OpsiSoal;
use App\Models\PelanggaranLog;
use App\Models\SesiUjian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\PaketSoalService;
use App\Services\UjianService;
use App\Support\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Random\Engine\Mt19937;
use Random\Randomizer;

class UjianController extends BaseAppController implements HasMiddleware
{
    public function __construct(private readonly UjianService $ujianService)
    {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, $next) {
                // Mode ujian (/ujian/*): auth via session NISN, bukan Auth
                if ($request->session()->get('mode_ujian') || $request->attributes->get('ujian_nisn')) {
                    return $next($request);
                }
                if ($request->user()?->role !== 'siswa') {
                    abort(403);
                }

                return $next($request);
            }),
        ];
    }

    /**
     * Daftar ujian terbit untuk kelas siswa pada tahun ajaran aktif.
     */
    public function index(Request $request): Response
    {
        $nisn = $this->nisnSiswa($request);
        $kelasIds = $this->kelasIds($nisn);

        $ujians = Ujian::query()
            ->with(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'guru:id,nama_lengkap'])
            ->withCount('soals')
            ->where('status', 'terbit')
            // Ujian resmi (UTS/UAS/USBK) hanya via /ujian/login, jangan tampil di dashboard harian
            ->whereNotIn('kategori', Ujian::KATEGORI_BESAR)
            ->whereHas('guruKelas', fn ($q) => $q->whereIn('kelas_id', $kelasIds)->where('tahun_ajaran_id', $this->tahunAjaran?->id))
            ->orderByDesc('created_at')
            ->get();

        $pengerjaans = UjianPengerjaan::query()
            ->where('siswa_nisn', $nisn)
            ->whereIn('ujian_id', $ujians->pluck('id'))
            ->get()
            ->groupBy('ujian_id');

        return Inertia::render('siswa/Ujian/Index', [
            'ujians' => $ujians->map(function (Ujian $u) use ($pengerjaans, $nisn): array {
                $sesi = $pengerjaans->get($u->id, collect());
                $selesai = $sesi->whereIn('status', ['selesai', 'auto_submit', 'diskualifikasi']);
                $berlangsung = $sesi->firstWhere('status', 'berlangsung');

                return [
                    'id' => $u->id,
                    'judul' => $u->judul,
                    'kategori' => $u->kategori,
                    'matpel' => $u->guruKelas?->matpel?->name,
                    'guru' => $u->guru?->nama_lengkap,
                    'durasi_menit' => $u->durasi_menit,
                    'jumlah_soal' => $u->soals_count,
                    'tanggal_mulai' => $u->tanggal_mulai?->translatedFormat('d M Y H:i'),
                    'tanggal_selesai' => $u->tanggal_selesai?->translatedFormat('d M Y H:i'),
                    'sedang_berlangsung' => $u->sedangBerlangsung(),
                    'butuh_token' => $u->token !== null,
                    'attempt_terpakai' => $selesai->count(),
                    'maks_attempt' => $u->maks_attempt,
                    'sisa_attempt' => max(0, $u->maks_attempt - $selesai->count()),
                    'ada_menunggu_koreksi' => $selesai->contains(fn ($p) => $p->nilai_total === null),
                    'berhak_remedial' => $selesai->count() >= $u->maks_attempt && $u->butuhRemedial($nisn),
                    'sedang_dikerjakan' => $berlangsung !== null,
                    'tampilkan_hasil' => $u->tampilkan_hasil,
                    'nilai_total' => $u->tampilkan_hasil ? $selesai->max('nilai_total') : null,
                ];
            }),
        ]);
    }

    /**
     * Waktu server (ISO8601 + unix) untuk sinkronisasi countdown di browser.
     * Countdown tidak boleh mengandalkan jam perangkat siswa.
     */
    public function waktuServer(Request $request): JsonResponse
    {
        $now = now();

        return response()->json([
            'server_time' => $now->toIso8601String(),
            'unix' => $now->timestamp,
        ]);
    }

    /**
     * Mulai atau lanjutkan pengerjaan (validasi token & attempt di server).
     */
    /**
     * Halaman konfirmasi pra-ujian: informasi ujian + tata tertib yang wajib
     * disetujui siswa sebelum mulai mengerjakan.
     */
    public function konfirmasi(Request $request, Ujian $ujian): Response|RedirectResponse
    {
        $ujian->loadMissing(['guruKelas.matpel', 'guruKelas.kelas', 'guru']);
        $nisn = $this->nisnSiswa($request);
        $this->pastikanBolehAkses($ujian, $nisn);
        $modeUjian = (bool) $request->session()->get('mode_ujian');

        if (! $ujian->sedangBerlangsung()) {
            Toast::error('Ujian belum dibuka atau sudah berakhir.');

            return $modeUjian
                ? Redirect::route('ujian.daftar')
                : Redirect::route('app.siswa.ujian.index');
        }

        // Sudah ada sesi berjalan -> langsung lanjutkan.
        if ($pengerjaan = $this->sesiBerjalan($ujian, $nisn)) {
            return $modeUjian
                ? Redirect::route('ujian.kerjakan', $ujian)
                : Redirect::route('app.siswa.ujian.kerjakan', $ujian);
        }

        $ujian->loadCount('soals');

        $adaMenungguKoreksi = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('siswa_nisn', $nisn)
            ->whereIn('status', ['selesai', 'auto_submit'])
            ->whereNull('nilai_total')
            ->exists();

        // Token per sesi: cari sesi yang sedang berlangsung untuk info
        $sesiAktif = $this->sesiUntukSiswa($ujian, $nisn);

        $data = [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'matpel' => $ujian->guruKelas?->matpel?->name,
                'guru' => $ujian->guru?->nama_lengkap,
                'deskripsi' => $ujian->deskripsi,
                'tata_tertib' => $ujian->tata_tertib,
                'durasi_menit' => $ujian->durasi_menit,
                'jumlah_soal' => $ujian->soals_count,
                'nilai_maks' => $ujian->nilai_maks,
                'butuh_token' => $sesiAktif?->token !== null,
                'wajib_fullscreen' => $ujian->wajib_fullscreen,
                'maks_pelanggaran' => $ujian->maks_pelanggaran,
                'ada_menunggu_koreksi' => $adaMenungguKoreksi,
                'sesi_nama' => $sesiAktif?->nama,
            ],
        ];

        if ($modeUjian) {
            $siswa = Siswa::where('nisn', $nisn)->first();
            $data['siswa'] = [
                'nama' => $siswa?->nama_lengkap,
                'nisn' => $nisn,
            ];

            return Inertia::render('ujian/Konfirmasi', $data);
        }

        return Inertia::render('siswa/Ujian/Konfirmasi', $data);
    }

    public function mulai(Request $request, Ujian $ujian): RedirectResponse
    {
        $nisn = $this->nisnSiswa($request);
        $this->pastikanBolehAkses($ujian, $nisn);

        // Wajib menyetujui tata tertib sebelum mulai.
        $request->validate([
            'setuju_tata_tertib' => ['required', 'accepted'],
        ], [], [
            'setuju_tata_tertib' => 'Persetujuan tata tertib',
        ]);

        if (! $ujian->sedangBerlangsung()) {
            Toast::error('Ujian belum dibuka atau sudah berakhir.');

            return Redirect::back();
        }

        // Sesi berjalan yang masih valid -> lanjutkan.
        $berjalan = $this->sesiBerjalan($ujian, $nisn);
        if ($berjalan) {
            Toast::info('Melanjutkan pengerjaan ujian.');

            $isModeUjian = str_starts_with($request->path(), 'ujian/');

            return $isModeUjian
                ? redirect("/ujian/{$ujian->id}/kerjakan")
                : Redirect::route('app.siswa.ujian.kerjakan', $ujian);
        }

        // Token per sesi (untuk ujian resmi), per ujian (untuk kuis)
        $sesi = $this->sesiUntukSiswa($ujian, $nisn);
        $tokenSesi = $sesi?->token;
        $tokenDibuka = $sesi?->token_released_at !== null;

        if ($tokenSesi !== null) {
            $request->validate(['token' => ['required', 'string']], ['token.required' => 'Token wajib diisi. Minta token kepada pengawas.']);

            $inputToken = $request->string('token')->toString();
            if ($inputToken !== $tokenSesi || ! $tokenDibuka) {
                Toast::error('Token salah atau belum dibuka pengawas.');

                return Redirect::back();
            }
        } elseif ($ujian->token !== null) {
            $request->validate(['token' => ['required', 'string']], ['token.required' => 'Token wajib diisi. Minta token kepada pengawas.']);

            if (! $ujian->tokenValid($request->string('token')->toString())) {
                Toast::error('Token salah atau belum dirilis pengawas.');

                return Redirect::back();
            }
        }

        $terpakai = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('siswa_nisn', $nisn)
            ->whereIn('status', ['selesai', 'auto_submit', 'diskualifikasi'])
            ->count();

        // Remedial: siswa di bawah KKM mendapat 1 kesempatan tambahan bertanda remedial.
        $isRemedial = false;
        if ($terpakai >= $ujian->maks_attempt) {
            $sudahRemedial = UjianPengerjaan::where('ujian_id', $ujian->id)
                ->where('siswa_nisn', $nisn)
                ->where('is_remedial', true)
                ->exists();

            if (! $sudahRemedial && $ujian->butuhRemedial($nisn)) {
                $isRemedial = true;
            } else {
                Toast::error('Kesempatan mengerjakan sudah habis.');

                return Redirect::back();
            }
        }

        // Ujian bersesi: tentukan sesi siswa, lalu buat pengerjaan dalam transaksi
        // terkunci agar kapasitas tidak jebol oleh request bersamaan.
        $pakaiSesi = $ujian->memakaiSesi();

        $pengerjaan = DB::transaction(function () use ($ujian, $nisn, $pakaiSesi, $isRemedial) {
            // Cek sesi berjalan di dalam transaction dengan lock (cegah double-click)
            $berjalan = UjianPengerjaan::where('ujian_id', $ujian->id)
                ->where('siswa_nisn', $nisn)
                ->where('status', 'berlangsung')
                ->lockForUpdate()
                ->latest('id')
                ->first();
            if ($berjalan) {
                return $berjalan;
            }

            // Hitung attempt di dalam transaction
            $terpakai = UjianPengerjaan::where('ujian_id', $ujian->id)
                ->where('siswa_nisn', $nisn)
                ->whereIn('status', ['selesai', 'auto_submit', 'diskualifikasi'])
                ->count();
            $sesiAktif = $pakaiSesi ? $this->sesiUntukSiswa($ujian, $nisn) : null;

            if ($pakaiSesi && $sesiAktif === null) {
                // Ditolak: sesiUntukSiswa sudah menampilkan Toast penolakan.
                return null;
            }

            $mulai = now();
            $batas = $mulai->copy()->addMinutes($ujian->durasi_menit);

            // Ala USBK: pengerjaan tidak boleh melewati akhir sesi.
            if ($sesiAktif !== null && $batas->gt($sesiAktif->waktu_selesai)) {
                $batas = $sesiAktif->waktu_selesai->copy();
            }

            if ($ujian->tanggal_selesai !== null && $batas->gt($ujian->tanggal_selesai)) {
                $batas = $ujian->tanggal_selesai;
            }

            // CBT v2: ujian berpake -> distribusikan paket soal (round-robin per sesi).
            $paket = null;
            if ($ujian->memakaiPaketSoal()) {
                $paket = app(PaketSoalService::class)->tentukanPaketUntukSiswa($ujian, $sesiAktif?->id);
            }

            return UjianPengerjaan::create([
                'ujian_id' => $ujian->id,
                'siswa_nisn' => $nisn,
                'sesi_ujian_id' => $sesiAktif?->id,
                'paket_soal_id' => $paket?->id,
                'attempt_ke' => $terpakai + 1,
                'is_remedial' => $isRemedial,
                'mulai_at' => $mulai,
                'batas_at' => $batas,
                'status' => 'berlangsung',
            ]);
        });

        if ($pengerjaan === null) {
            return Redirect::back();
        }

        Toast::success($isRemedial ? 'Remedial dimulai. Kerjakan dengan jujur.' : 'Ujian dimulai. Kerjakan dengan jujur.');

        return $request->session()->get('mode_ujian')
            ? Redirect::route('ujian.kerjakan', $ujian)
            : Redirect::route('app.siswa.ujian.kerjakan', $ujian);
    }

    /**
     * Tentukan sesi yang boleh dimasuki siswa pada ujian bersesi.
     * Mengembalikan null (dengan Toast penolakan) bila siswa tidak boleh masuk.
     */
    private function sesiUntukSiswa(Ujian $ujian, string $nisn): ?SesiUjian
    {
        // Kuis/ulangan harian tidak pakai sesi - langsung boleh masuk
        if (! in_array($ujian->kategori, ['uts', 'uas', 'usbk'], true)) {
            return null;
        }
        if ($ujian->sesiTerjadwal()) {
            // Ala USBK: siswa hanya boleh masuk sesi yang dijatahkan untuknya.
            $jatah = SesiUjian::jatahUntuk($ujian->id, $nisn);

            if ($jatah === null) {
                Toast::error('Anda belum dijadwalkan ke sesi ujian manapun. Hubungi guru.');

                return null;
            }

            // Kunci baris sesi agar pengecekan kapasitas aman dari race.
            $jatah = SesiUjian::whereKey($jatah->id)->lockForUpdate()->first();

            if (! $jatah->sedangAktif()) {
                $kapan = $jatah->waktu_mulai->isFuture() ? 'belum dimulai' : 'sudah berakhir';
                Toast::error("Sesi Anda ({$jatah->nama}) {$kapan}. Jadwal: {$jatah->waktu_mulai->format('H:i')}–{$jatah->waktu_selesai->format('H:i')}.");

                return null;
            }

            return $jatah;
        }

        // Mode bebas: sesi aktif mana saja, kapasitas first-come.
        $sesiAktif = SesiUjian::query()
            ->where('ujian_id', $ujian->id)
            ->where('waktu_mulai', '<=', now())
            ->where('waktu_selesai', '>=', now())
            ->orderBy('urutan')
            ->lockForUpdate()
            ->first();

        if ($sesiAktif === null) {
            Toast::error('Tidak ada sesi ujian yang sedang berlangsung.');

            return null;
        }

        if ($sesiAktif->penuh()) {
            Toast::error("{$sesiAktif->nama} sudah penuh. Tunggu sesi berikutnya.");

            return null;
        }

        return $sesiAktif;
    }

    /**
     * Halaman runtime pengerjaan.
     */
    public function kerjakan(Request $request, Ujian $ujian): Response|RedirectResponse
    {
        $nisn = $this->nisnSiswa($request);
        $this->pastikanBolehAkses($ujian, $nisn);

        $pengerjaan = $this->sesiBerjalan($ujian, $nisn);

        if (! $pengerjaan) {
            // Cek apakah waktu habis pada sesi terakhir dan finalisasi.
            $terakhir = UjianPengerjaan::where('ujian_id', $ujian->id)
                ->where('siswa_nisn', $nisn)
                ->where('status', 'berlangsung')
                ->latest('id')
                ->first();

            if ($terakhir && $terakhir->waktuHabis()) {
                $this->finalisasi($terakhir, 'auto_submit');
                Toast::warning('Waktu ujian habis. Jawaban dikumpulkan otomatis.');
            }

            return $request->session()->get('mode_ujian')
                ? Redirect::route('ujian.daftar')
                : Redirect::route('app.siswa.ujian.index');
        }

        $ujian->load('soals.opsi');

        // CBT v2: bila pengerjaan memakai paket soal, siswa hanya melihat soal paketnya.
        $soals = $pengerjaan->paket_soal_id !== null
            ? $ujian->soals->where('paket_soal_id', $pengerjaan->paket_soal_id)->values()
            : $ujian->soals;

        // Buat objek tiruan agar urutkanSoal tetap bekerja dengan koleksi terfilter.
        $ujian->setRelation('soals', $soals);
        $soals = $this->urutkanSoal($ujian, $pengerjaan);

        $jawabanBySoal = $pengerjaan->jawabans()->get()->keyBy('soal_id');

        return Inertia::render('siswa/Ujian/Kerjakan', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'wajib_fullscreen' => $ujian->wajib_fullscreen,
                'maks_pelanggaran' => $ujian->maks_pelanggaran,
            ],
            'pengerjaan' => [
                'id' => $pengerjaan->id,
                'batas_at' => $pengerjaan->batas_at->toIso8601String(),
                'sisa_detik' => $pengerjaan->sisaDetik(),
                'jumlah_pelanggaran' => $pengerjaan->jumlah_pelanggaran,
            ],
            'sesi' => $pengerjaan->sesi ? [
                'nama' => $pengerjaan->sesi->nama,
                'waktu_selesai' => $pengerjaan->sesi->waktu_selesai->toIso8601String(),
            ] : null,
            'soals' => $soals->map(fn (Soal $s): array => [
                'id' => $s->id,
                'tipe' => $s->tipe,
                'pertanyaan' => $s->pertanyaan,
                'poin' => $s->poin,
                'media_url' => $s->media_path ? Storage::url($s->media_path) : null,
                'media_type' => $s->media_type,
                'opsi' => $s->butuhOpsi()
                    ? $this->urutkanOpsi($s, $ujian, $pengerjaan)->map(fn ($o): array => [
                        'id' => $o->id,
                        'teks' => $o->teks,
                    ])->values()
                    : [],
                'detail' => $this->stripKunci($s->detail),
                'jawaban' => [
                    'opsi_dipilih' => $jawabanBySoal->get($s->id)?->opsi_dipilih,
                    'jawaban_teks' => $jawabanBySoal->get($s->id)?->jawaban_teks,
                    'ragu' => (bool) ($jawabanBySoal->get($s->id)?->ragu ?? false),
                ],
            ])->values(),
        ]);
    }

    /**
     * Autosave satu jawaban.
     */
    public function simpanJawaban(Request $request, Ujian $ujian): RedirectResponse|JsonResponse
    {
        $nisn = $this->nisnSiswa($request);
        $this->pastikanBolehAkses($ujian, $nisn);

        $pengerjaan = $this->sesiBerjalan($ujian, $nisn);
        if (! $pengerjaan) {
            return Redirect::back();
        }

        $data = $request->validate([
            'soal_id' => ['required', Rule::exists('cbt_soals', 'id')->where('ujian_id', $ujian->id)],
            'opsi_dipilih' => ['nullable', 'array'],
            'opsi_dipilih.*' => ['integer'],
            'jawaban_teks' => ['nullable', 'string', 'max:20000'],
            'ragu' => ['nullable', 'boolean'],
        ]);

        if ($pengerjaan->waktuHabis()) {
            $this->finalisasi($pengerjaan, 'auto_submit');
            Toast::warning('Waktu ujian habis. Jawaban dikumpulkan otomatis.');

            return Redirect::route('app.siswa.ujian.index');
        }

        JawabanSiswa::updateOrCreate(
            ['ujian_pengerjaan_id' => $pengerjaan->id, 'soal_id' => $data['soal_id']],
            [
                'opsi_dipilih' => $data['opsi_dipilih'] ?? null,
                'jawaban_teks' => $data['jawaban_teks'] ?? null,
                'ragu' => $data['ragu'] ?? false,
            ],
        );

        // Jika fetch/AJAX, kembalikan JSON ringan (bukan redirect Inertia)
        if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['ok' => true]);
        }

        return Redirect::back();
    }

    /**
     * Kumpulkan ujian (auto-grade objektif).
     */
    public function submit(Request $request, Ujian $ujian): RedirectResponse
    {
        $nisn = $this->nisnSiswa($request);
        $this->pastikanBolehAkses($ujian, $nisn);
        $modeUjian = (bool) $request->session()->get('mode_ujian');

        $pengerjaan = $this->sesiBerjalan($ujian, $nisn);
        if (! $pengerjaan) {
            Toast::error('Sesi ujian tidak ditemukan atau sudah berakhir.');

            return $modeUjian
                ? Redirect::route('ujian.daftar')
                : Redirect::route('app.siswa.ujian.index');
        }

        $this->finalisasi($pengerjaan, 'selesai');

        Toast::success('Ujian berhasil dikumpulkan.');

        return $modeUjian
            ? Redirect::route('ujian.hasil', $ujian)
            : Redirect::route('app.siswa.ujian.hasil', $ujian);
    }

    /**
     * Catat pelanggaran proctoring; diskualifikasi bila melebihi ambang.
     */
    public function lapor(Request $request, Ujian $ujian): RedirectResponse
    {
        $nisn = $this->nisnSiswa($request);
        $this->pastikanBolehAkses($ujian, $nisn);

        $pengerjaan = $this->sesiBerjalan($ujian, $nisn);
        if (! $pengerjaan) {
            return Redirect::back();
        }

        $data = $request->validate([
            'jenis' => ['required', Rule::in(PelanggaranLog::JENIS)],
        ]);

        $pengerjaan->pelanggarans()->create([
            'jenis' => $data['jenis'],
            'terjadi_at' => now(),
        ]);

        $pengerjaan->increment('jumlah_pelanggaran');

        if ($ujian->maks_pelanggaran > 0 && $pengerjaan->jumlah_pelanggaran >= $ujian->maks_pelanggaran) {
            $this->finalisasi($pengerjaan, 'diskualifikasi');
            Toast::error('Anda melewati batas pelanggaran. Ujian dihentikan otomatis.');

            return Redirect::route('app.siswa.ujian.index');
        }

        return Redirect::back();
    }

    /**
     * Halaman hasil ujian siswa.
     */
    public function hasil(Request $request, Ujian $ujian): Response|RedirectResponse
    {
        $nisn = $this->nisnSiswa($request);
        $this->pastikanBolehAkses($ujian, $nisn);
        $modeUjian = (bool) $request->session()->get('mode_ujian');

        $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('siswa_nisn', $nisn)
            ->whereIn('status', ['selesai', 'auto_submit', 'diskualifikasi'])
            ->latest('id')
            ->first();

        if (! $pengerjaan) {
            Toast::info('Kamu belum menyelesaikan ujian ini.');

            return $modeUjian
                ? Redirect::route('ujian.daftar')
                : Redirect::route('app.siswa.ujian.index');
        }

        // Jangan kirim nilai ke browser sama sekali bila guru menonaktifkan
        // tampilkan_hasil (mencegah intip via devtools sebelum pengumuman).
        $bolehLihatNilai = (bool) $ujian->tampilkan_hasil;

        $data = [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'nilai_maks' => $ujian->nilai_maks,
                'tampilkan_hasil' => $ujian->tampilkan_hasil,
            ],
            'pengerjaan' => [
                'status' => $pengerjaan->status,
                'nilai_objektif' => $bolehLihatNilai ? $pengerjaan->nilai_objektif : null,
                'nilai_esai' => $bolehLihatNilai ? $pengerjaan->nilai_esai : null,
                'nilai_total' => $bolehLihatNilai ? $pengerjaan->nilai_total : null,
                'submitted_at' => $pengerjaan->submitted_at?->translatedFormat('d M Y H:i'),
                'menunggu_koreksi' => $pengerjaan->nilai_total === null,
            ],
        ];

        if ($modeUjian) {
            $siswa = Siswa::where('nisn', $nisn)->first();
            $data['siswa'] = [
                'nama' => $siswa?->nama_lengkap,
                'nisn' => $nisn,
            ];

            return Inertia::render('ujian/Hasil', $data);
        }

        return Inertia::render('siswa/Ujian/Hasil', $data);
    }

    /**
     * Finalisasi sesi: auto-grade objektif lalu hitung nilai.
     */
    private function finalisasi(UjianPengerjaan $pengerjaan, string $status): void
    {
        DB::transaction(function () use ($pengerjaan, $status) {
            // Lock row untuk cegah double submit
            $p = UjianPengerjaan::whereKey($pengerjaan->id)->lockForUpdate()->first();
            if (! $p || $p->status !== 'berlangsung') {
                return; // Sudah difinalisasi
            }

            $p->update([
                'status' => $status,
                'submitted_at' => now(),
            ]);

            $this->ujianService->gradeObjektif($p);
            $this->ujianService->rekalkulasiNilai($p->fresh());
        });
    }

    private function sesiBerjalan(Ujian $ujian, string $nisn): ?UjianPengerjaan
    {
        $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('siswa_nisn', $nisn)
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();

        if ($pengerjaan && $pengerjaan->waktuHabis()) {
            $this->finalisasi($pengerjaan, 'auto_submit');

            return null;
        }

        return $pengerjaan;
    }

    /**
     * Ambil NISN siswa: dari session mode ujian jika ada, else dari user login.
     */
    private function nisnSiswa(Request $request): string
    {
        return $request->attributes->get('ujian_nisn')
            ?? $request->session()->get('ujian_nisn')
            ?? $request->user()->siswa->nisn;
    }

    private function pastikanBolehAkses(Ujian $ujian, string $nisn): void
    {
        abort_unless($ujian->status === 'terbit', 404);
        abort_unless($ujian->guruKelas !== null, 404);

        // Mode ujian (/ujian/*): verifikasi siswa terdaftar di ujian ini.
        // Untuk ujian terjadwal: cek jatah sesi. Untuk lainnya: cek kelas.
        if (request()->session()->get('mode_ujian')) {
            if ($ujian->sesiTerjadwal()) {
                abort_unless(SesiUjian::jatahUntuk($ujian->id, $nisn) !== null, 404);
            } else {
                abort_unless($this->kelasIds($nisn)->contains($ujian->guruKelas->kelas_id), 404);
            }

            return;
        }
        abort_unless($this->kelasIds($nisn)->contains($ujian->guruKelas->kelas_id), 404);
        abort_unless($ujian->guruKelas->tahun_ajaran_id === $this->tahunAjaran?->id, 404);
    }

    /**
     * @return Collection<int, int>
     */
    private function kelasIds(string $nisn): Collection
    {
        return SiswaKelas::query()
            ->where('siswa_nisn', $nisn)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->where('active', true)
            ->pluck('kelas_id');
    }

    /**
     * Urutan soal per attempt: stabil di dalam satu pengerjaan, beda antar attempt.
     *
     * @return Collection<int, Soal>
     */
    /**
     * Hapus kunci jawaban dari detail sebelum dikirim ke siswa.
     * Mencegah siswa melihat kunci via dev tools.
     */
    private function stripKunci(?array $detail): ?array
    {
        if (empty($detail)) {
            return $detail;
        }

        $d = $detail;
        unset($d['kunci']);

        // Benar/salah: hapus jawaban per pernyataan
        if (isset($d['pernyataan']) && is_array($d['pernyataan'])) {
            foreach ($d['pernyataan'] as &$p) {
                unset($p['jawaban']);
            }
            unset($p);
        }

        // Image labeling: hapus label/kunci, sisakan koordinat
        if (isset($d['markers']) && is_array($d['markers'])) {
            foreach ($d['markers'] as &$m) {
                unset($m['label'], $m['kunci']);
            }
            unset($m);
        }

        // Menjodohkan: kirim kiri & kanan terpisah (frontend acak deterministik)
        if (isset($d['pasangan']) && is_array($d['pasangan'])) {
            $kiri = [];
            $kanan = [];
            foreach ($d['pasangan'] as $p) {
                $kiri[] = $p['kiri'] ?? '';
                $kanan[] = $p['kanan'] ?? '';
            }
            // JANGAN shuffle di sini - frontend sudah shuffle deterministik
            $d['kiri'] = $kiri;
            $d['kanan'] = $kanan;
            unset($d['pasangan']);
        }

        // Mengurutkan: acak urutan di tempat (renderer butuh 'urutan')
        if (isset($d['urutan']) && is_array($d['urutan'])) {
            shuffle($d['urutan']);
        }
        unset($d['urutan_benar']);

        // Mengelompokkan: kirim nama kelompok + pool item acak
        if (isset($d['kelompok']) && is_array($d['kelompok'])) {
            $namaKelompok = [];
            $pool = [];
            foreach ($d['kelompok'] as $k) {
                $namaKelompok[] = $k['nama'] ?? '';
                foreach ($k['items'] ?? [] as $item) {
                    $pool[] = $item;
                }
            }
            shuffle($pool);
            $d['nama_kelompok'] = $namaKelompok;
            $d['pool'] = $pool;
            unset($d['kelompok']);
        }

        unset($d['jawaban']);

        return $d;
    }

    private function urutkanSoal(Ujian $ujian, UjianPengerjaan $pengerjaan): Collection
    {
        $soals = $ujian->soals;

        if ($ujian->acak_soal) {
            return $this->acakDeterministik($soals, $this->seedSoal($pengerjaan));
        }

        return $soals->values();
    }

    /**
     * Acak deterministik dengan seed — hasil stabil untuk seed yang sama,
     * berbeda untuk seed berbeda. Tidak menyentuh state random global PHP.
     *
     * @return Collection<int, mixed>
     */
    private function acakDeterministik(Collection $items, int $seed): Collection
    {
        $list = $items->values()->all();

        if (count($list) <= 1) {
            return collect($list);
        }

        $randomizer = new Randomizer(new Mt19937($seed));

        return collect($randomizer->shuffleArray($list));
    }

    /**
     * Urutan opsi per soal: stabil, beda antar soal & antar attempt.
     *
     * @return Collection<int, OpsiSoal>
     */
    private function urutkanOpsi(Soal $soal, Ujian $ujian, UjianPengerjaan $pengerjaan): Collection
    {
        if ($ujian->acak_opsi) {
            return $this->acakDeterministik($soal->opsi, $this->seedOpsi($pengerjaan, $soal));
        }

        return $soal->opsi->values();
    }

    /**
     * Seed acak untuk urutan soal — unik per attempt.
     */
    private function seedSoal(UjianPengerjaan $pengerjaan): int
    {
        return (int) (crc32("soal:{$pengerjaan->id}:{$pengerjaan->attempt_ke}") & 0x7FFFFFFF);
    }

    /**
     * Seed acak untuk urutan opsi — unik per attempt DAN per soal.
     */
    private function seedOpsi(UjianPengerjaan $pengerjaan, Soal $soal): int
    {
        return (int) (crc32("opsi:{$pengerjaan->id}:{$soal->id}:{$pengerjaan->attempt_ke}") & 0x7FFFFFFF);
    }

    /**
     * Download kartu ujian siswa (PDF) — berisi kredensial login ujian.
     */
    public function kartuSaya(Request $request): \Illuminate\Http\Response
    {
        $siswa = $request->user()->siswa;
        abort_unless($siswa, 404);

        $kelasId = SiswaKelas::where('siswa_nisn', $siswa->nisn)
            ->where('active', true)->value('kelas_id');
        $kelas = $kelasId ? Kelas::find($kelasId) : null;

        // Password universal — cukup cek siswa sudah punya password
        abort_unless($siswa->password_ujian, 404, 'Kartu belum tersedia. Hubungi panitia untuk membuat password ujian.');

        // Dekripsi password
        $passwordPlain = null;
        if ($siswa->password_ujian) {
            try {
                $passwordPlain = Crypt::decryptString($siswa->password_ujian);
            } catch (\Exception $e) {
                $passwordPlain = null;
            }
        }
        $siswa->password_ujian_plain = $passwordPlain;

        // Ujian resmi yang terbit untuk kelas siswa
        $kelasId = SiswaKelas::where('siswa_nisn', $siswa->nisn)
            ->where('active', true)->value('kelas_id');
        $kelas = $kelasId ? Kelas::find($kelasId) : null;

        $ujians = Ujian::query()
            ->where('status', 'terbit')
            ->whereIn('kategori', Ujian::KATEGORI_BESAR)
            ->whereHas('guruKelas', fn ($q) => $q->where('kelas_id', $kelasId))
            ->with(['sesis.ruang', 'guruKelas.matpel'])
            ->orderBy('id')->get();

        Carbon::setLocale('id');

        $pdf = Pdf::loadView('pdf.kartu-peserta-siswa', [
            'siswa' => $siswa,
            'kelas' => $kelas,
            'ujians' => $ujians,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("kartu-ujian-{$siswa->nisn}.pdf");
    }
}
