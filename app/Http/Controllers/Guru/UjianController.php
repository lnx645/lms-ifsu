<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Penilaian;
use App\Models\PeriodeUjian;
use App\Models\SesiUjian;
use App\Models\SesiUjianPeserta;
use App\Models\SiswaKelas;
use App\Models\Ujian;
use App\Services\BankSoalService;
use App\Services\KelengkapanUjianService;
use App\Services\NotifikasiService;
use App\Services\PoolSoalService;
use App\Services\UjianService;
use App\Support\Toast;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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
                if ($request->user()?->role !== 'guru') {
                    abort(403);
                }

                return $next($request);
            }),
        ];
    }

    /**
     * Daftar ujian milik guru untuk tahun ajaran aktif.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('guru/Ujian/Index', $this->indexData($request));
    }

    public function create(Request $request): Response
    {
        return Inertia::render('guru/Ujian/Create', [
            'penugasan' => $this->penugasan($request->user()->guru),
        ]);
    }

    /**
     * Simpan ujian baru + buat penilaian terkait.
     */
    public function store(Request $request): RedirectResponse
    {
        $guru = $request->user()->guru;
        $data = $this->validateUjian($request, $guru);

        $ujian = new Ujian($data);
        $ujian->guru_id = $guru->id;
        $ujian->dibuat_oleh_admin = false;
        $ujian->status = 'draft';
        $ujian->save();

        $this->buatPenilaian($ujian);

        Toast::success('Ujian "'.$ujian->judul.'" berhasil dibuat.');

        return Redirect::back();
    }

    /**
     * Data ujian milik guru untuk modal edit.
     */
    public function edit(Request $request, Ujian $ujian): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        return Inertia::render('guru/Ujian/Edit', [
            'ujian' => $this->serializeEdit($ujian),
            'penugasan' => $this->penugasan($request->user()->guru),
        ]);
    }

    public function update(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $guru = $request->user()->guru;
        $data = $this->validateUjian($request, $guru);

        $ujian->fill($data)->save();

        if ($ujian->penilaian_id) {
            $ujian->penilaian()->update([
                'nama' => $this->namaPenilaian($ujian),
                'nilai_maks' => $ujian->nilai_maks,
                'bobot' => $ujian->bobot,
            ]);
        }

        Toast::success('Ujian "'.$ujian->judul.'" berhasil diperbarui.');

        return Redirect::back();
    }

    public function destroy(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        // Jangan hapus ujian yang sudah dikerjakan: nilai siswa ikut musnah.
        if ($ujian->pengerjaans()->exists()) {
            Toast::error('Ujian tidak dapat dihapus karena sudah dikerjakan siswa.');

            return Redirect::back();
        }

        $penilaianId = $ujian->penilaian_id;
        $ujian->delete();

        if ($penilaianId) {
            Penilaian::where('id', $penilaianId)->delete();
        }

        Toast::success('Ujian berhasil dihapus.');

        return Redirect::back();
    }

    /**
     * Terbitkan / tarik ujian. Ujian besar wajib punya minimal 1 soal.
     */
    public function simpanPool(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $data = $request->validate([
            'pool' => ['required', 'array', 'min:1'],
            'pool.*.kelompok_bank_soal_id' => ['required', 'integer', 'exists:cbt_kelompok_bank_soals,id'],
            'pool.*.jumlah' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $ujian->update(['pool_soal' => $data['pool']]);

        // Langsung generate soal acak dari pool
        $bankIds = app(PoolSoalService::class)
            ->pilihAcak($data['pool']);

        if (! empty($bankIds)) {
            app(BankSoalService::class)
                ->salinKeUjian($ujian, $bankIds);
        }

        Toast::success('Paket soal otomatis dibuat: '.count($bankIds).' soal.');

        return Redirect::back();
    }

    public function terbit(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        if ($ujian->status === 'draft') {
            // CBT v2: validasi kelengkapan sesuai kategori (ketat untuk ujian resmi).
            // Guru hanya cek tugasnya sendiri, bukan tugas panitia.
            $masalah = app(KelengkapanUjianService::class)->cekGuru($ujian);
            if ($masalah !== []) {
                Toast::error('Belum bisa diterbitkan: '.implode(' ', $masalah));

                return Redirect::back();
            }

            $ujian->update(['status' => 'terbit']);
            app(NotifikasiService::class)->jadwalUjian($ujian->fresh());
            Toast::success('Ujian diterbitkan dan notifikasi jadwal dikirim ke siswa.');
        } else {
            // Jangan tarik ke draft jika ada siswa sedang mengerjakan
            $sedangMengerjakan = $ujian->pengerjaans()->where('status', 'berlangsung')->exists();
            if ($sedangMengerjakan) {
                Toast::error('Tidak bisa ditarik ke draft: ada siswa sedang mengerjakan ujian.');

                return Redirect::back();
            }
            $ujian->update(['status' => 'draft']);
            Toast::info('Ujian ditarik ke draft.');
        }

        return Redirect::back();
    }

    /**
     * Buat / regenerasi token acak untuk ujian.
     */
    public function generateToken(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        if ($ujian->token_pengelola !== 'guru') {
            Toast::error('Token ujian ini dikelola oleh admin.');

            return Redirect::back();
        }

        $ujian->update([
            'token' => $this->ujianService->generateToken(),
            'token_released_at' => null,
        ]);

        Toast::success('Token baru dibuat. Rilis token saat siswa siap.');

        return Redirect::back();
    }

    /**
     * Rilis / tahan token (buka gerbang pengerjaan).
     */
    public function toggleToken(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        if ($ujian->token_pengelola !== 'guru') {
            Toast::error('Token ujian ini dikelola oleh admin.');

            return Redirect::back();
        }

        if ($ujian->token === null) {
            Toast::error('Buat token terlebih dahulu.');

            return Redirect::back();
        }

        if ($ujian->token_released_at === null) {
            $ujian->update(['token_released_at' => now()]);
            Toast::success('Token dirilis. Siswa dapat memulai ujian.');
        } else {
            $ujian->update(['token_released_at' => null]);
            Toast::info('Token ditahan.');
        }

        return Redirect::back();
    }

    /**
     * Tambah gelombang sesi ujian (standar nasional).
     */
    public function sesiStore(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_if(in_array($ujian->kategori, ['uts', 'uas', 'usbk']), 403, 'Sesi ujian resmi dikelola panitia.');

        $data = $this->validateSesi($request, $ujian);

        $ujian->sesis()->create($data);

        Toast::success("Sesi \"{$data['nama']}\" ditambahkan.");

        return Redirect::back();
    }

    /**
     * Ubah gelombang sesi ujian.
     */
    public function sesiUpdate(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($sesi->ujian_id === $ujian->id, 404);
        abort_if(in_array($ujian->kategori, ['uts', 'uas', 'usbk']), 403, 'Sesi ujian resmi dikelola panitia.');

        $data = $this->validateSesi($request, $ujian, $sesi);

        // Waktu sesi yang sedang dipakai siswa tidak boleh diubah.
        $waktuBerubah = isset($data['waktu_mulai'], $data['waktu_selesai'])
            && ($data['waktu_mulai'] !== $sesi->waktu_mulai->format('Y-m-d H:i')
                || $data['waktu_selesai'] !== $sesi->waktu_selesai->format('Y-m-d H:i'));

        if ($waktuBerubah && $sesi->pengerjaans()->where('status', 'berlangsung')->exists()) {
            Toast::error('Waktu sesi tidak dapat diubah karena ada siswa yang sedang mengerjakan.');

            return Redirect::back();
        }

        $sesi->update($data);

        Toast::success("Sesi \"{$data['nama']}\" diperbarui.");

        return Redirect::back();
    }

    /**
     * Hapus gelombang sesi ujian (tidak boleh bila sudah dipakai pengerjaan).
     */
    public function sesiDestroy(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($sesi->ujian_id === $ujian->id, 404);
        abort_if(in_array($ujian->kategori, ['uts', 'uas', 'usbk']), 403, 'Sesi ujian resmi dikelola panitia.');

        if ($sesi->pengerjaans()->exists()) {
            Toast::error('Sesi tidak dapat dihapus karena sudah dipakai pengerjaan siswa.');

            return Redirect::back();
        }

        $sesi->delete();

        Toast::success('Sesi dihapus.');

        return Redirect::back();
    }

    /**
     * Halaman kelola sesi ujian + penjadwalan siswa (ala USBK).
     */
    public function sesiIndex(Request $request, Ujian $ujian): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        // Ujian resmi dikelola panitia, guru tidak boleh atur sesi
        abort_if(in_array($ujian->kategori, ['uts', 'uas', 'usbk']), 403, 'Sesi ujian resmi dikelola panitia.');

        $ujian->load(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name']);

        $sesis = $ujian->sesis()
            ->withCount(['pengerjaans', 'pesertas'])
            ->with(['ruang:id,nama,kode'])
            ->get()
            ->map(fn (SesiUjian $sesi) => [
                'id' => $sesi->id,
                'nama' => $sesi->nama,
                'urutan' => $sesi->urutan,
                'waktu_mulai' => $sesi->waktu_mulai->format('Y-m-d H:i'),
                'waktu_selesai' => $sesi->waktu_selesai->format('Y-m-d H:i'),
                'kapasitas' => $sesi->kapasitas,
                'ruang' => $sesi->ruang ? ['id' => $sesi->ruang->id, 'nama' => $sesi->ruang->nama] : null,
                'sedang_aktif' => $sesi->sedangAktif(),
                'sudah_lewat' => $sesi->sudahLewat(),
                'jumlah_peserta' => $sesi->pesertas_count,
                'jumlah_terisi' => $sesi->pengerjaans_count,
                'bisa_hapus' => ! $sesi->pengerjaans()->exists(),
            ])
            ->values()
            ->all();

        // Daftar siswa kelas ujian pada tahun ajaran penugasan.
        /** @var array<int, array{nisn: string, nama: string}> $siswaKelas */
        $siswaKelas = [];
        $guruKelas = $ujian->guru_kelas_id ? GuruKelas::find($ujian->guru_kelas_id) : null;
        $tahunAjaranId = $guruKelas?->tahun_ajaran_id;
        $kelasId = $guruKelas?->kelas_id;

        if ($kelasId && $tahunAjaranId) {
            /** @var Collection<int, SiswaKelas> $daftar */
            $daftar = SiswaKelas::query()
                ->with('siswa:nisn,nama_lengkap')
                ->where('kelas_id', $kelasId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('active', true)
                ->get();

            $siswaKelas = $daftar
                ->map(fn (SiswaKelas $sk) => [
                    'nisn' => $sk->siswa_nisn,
                    'nama' => $sk->siswa?->nama_lengkap ?? $sk->siswa_nisn,
                ])
                ->sortBy('nama')
                ->values()
                ->all();
        }

        $pesertaMap = SesiUjianPeserta::query()
            ->where('ujian_id', $ujian->id)
            ->get(['sesi_ujian_id', 'siswa_nisn'])
            ->groupBy('sesi_ujian_id')
            ->map(fn ($rows) => $rows->pluck('siswa_nisn')->values()->all())
            ->all();

        return Inertia::render('guru/Ujian/Sesi', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'kelas' => $ujian->guruKelas?->kelas?->nama,
                'matpel' => $ujian->guruKelas?->matpel?->name,
                'tanggal_mulai' => $ujian->tanggal_mulai?->format('Y-m-d H:i'),
                'tanggal_selesai' => $ujian->tanggal_selesai?->format('Y-m-d H:i'),
                'durasi_menit' => $ujian->durasi_menit,
                'mode_sesi' => $ujian->mode_sesi ?? 'bebas',
            ],
            'sesis' => $sesis,
            'siswaKelas' => $siswaKelas,
            'pesertaMap' => $pesertaMap,
        ]);
    }

    /**
     * Ubah mode pembagian sesi: bebas (rebutan) atau terjadwal (dijatah, ala USBK).
     */
    public function sesiMode(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_if(in_array($ujian->kategori, ['uts', 'uas', 'usbk']), 403, 'Sesi ujian resmi dikelola panitia.');

        $data = $request->validate([
            'mode_sesi' => ['required', 'in:bebas,terjadwal'],
        ]);

        $ujian->update(['mode_sesi' => $data['mode_sesi']]);

        Toast::success($data['mode_sesi'] === 'terjadwal'
            ? 'Mode sesi: terjadwal (siswa dijatahkan per sesi, ala USBK).'
            : 'Mode sesi: bebas (siswa boleh masuk sesi aktif mana saja).');

        return Redirect::back();
    }

    /**
     * Jadwalkan siswa ke sebuah sesi (mode terjadwal).
     */
    public function sesiPesertaStore(Request $request, Ujian $ujian, SesiUjian $sesi): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        $data = $request->validate([
            'nisn' => ['required', 'array', 'min:1'],
            'nisn.*' => ['string', 'max:10'],
        ]);

        $sekarang = now();

        foreach ($data['nisn'] as $nisn) {
            SesiUjianPeserta::updateOrCreate(
                ['ujian_id' => $ujian->id, 'siswa_nisn' => $nisn],
                ['sesi_ujian_id' => $sesi->id, 'created_at' => $sekarang, 'updated_at' => $sekarang],
            );
        }

        // Kapasitas sesi mengikuti jumlah peserta bila mode terjadwal.
        if ($sesi->kapasitas !== null && $sesi->pesertas()->count() > $sesi->kapasitas) {
            Toast::warning('Peserta melebihi kapasitas sesi. Sesuaikan kapasitas bila perlu.');
        } else {
            Toast::success(count($data['nisn']).' siswa dijadwalkan ke '.$sesi->nama.'.');
        }

        return Redirect::back();
    }

    /**
     * Batalkan penjadwalan siswa dari sesi.
     */
    public function sesiPesertaDestroy(Request $request, Ujian $ujian, SesiUjian $sesi, string $nisn): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($sesi->ujian_id === $ujian->id, 404);

        SesiUjianPeserta::where('ujian_id', $ujian->id)
            ->where('sesi_ujian_id', $sesi->id)
            ->where('siswa_nisn', $nisn)
            ->delete();

        Toast::success('Penjadwalan siswa dibatalkan.');

        return Redirect::back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSesi(Request $request, Ujian $ujian, ?SesiUjian $kecuali = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['urutan'] ??= $ujian->sesis()->max('urutan') + 1;

        $mulai = Carbon::parse($data['waktu_mulai']);
        $selesai = Carbon::parse($data['waktu_selesai']);

        if ($ujian->tanggal_mulai !== null && $mulai->lt($ujian->tanggal_mulai)) {
            throw ValidationException::withMessages([
                'waktu_mulai' => 'Sesi tidak boleh mulai sebelum jadwal ujian.',
            ]);
        }

        if ($ujian->tanggal_selesai !== null && $selesai->gt($ujian->tanggal_selesai)) {
            throw ValidationException::withMessages([
                'waktu_selesai' => 'Sesi tidak boleh berakhir setelah jadwal ujian.',
            ]);
        }

        // Sesi ala USBK adalah gelombang berurutan: tidak boleh tumpang tindih.
        $tabrakan = $ujian->sesis()
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali->id))
            ->where('waktu_mulai', '<', $selesai)
            ->where('waktu_selesai', '>', $mulai)
            ->first();

        if ($tabrakan) {
            throw ValidationException::withMessages([
                'waktu_mulai' => "Waktu bertabrakan dengan \"{$tabrakan->nama}\". Sesi harus berurutan.",
            ]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function indexData(Request $request): array
    {
        $guru = $request->user()->guru;

        // Kategori bisa datang sebagai string atau objek {value, label} dari Select.
        $kategoriInput = $request->input('kategori');
        $kategori = is_array($kategoriInput)
            ? ($kategoriInput['value'] ?? null)
            : ($kategoriInput ?: null);

        $ujians = Ujian::query()
            ->with(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name'])
            ->withCount('soals')
            ->withCount('sesis')
            ->withCount(['pengerjaans as jumlah_selesai' => fn ($q) => $q->whereIn('status', ['selesai', 'auto_submit'])])
            ->where('guru_id', $guru->id)
            ->whereIn('kategori', ['kuis', 'uh'])
            ->whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $this->tahunAjaran?->id))
            ->when($request->integer('guru_kelas_id'), fn ($q, $id) => $q->where('guru_kelas_id', $id))
            ->when($kategori, fn ($q, $k) => $q->where('kategori', $k))
            ->when($request->string('q')->toString(), fn ($q, $kw) => $q->where('judul', 'like', "%{$kw}%"))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $ujians->through(fn (Ujian $u): array => [
            'id' => $u->id,
            'judul' => $u->judul,
            'kategori' => $u->kategori,
            'kelas' => $u->guruKelas?->kelas?->nama,
            'matpel' => $u->guruKelas?->matpel?->name,
            'status' => $u->status,
            'catatan_panitia' => $u->catatan_panitia,
            'durasi_menit' => $u->durasi_menit,
            'tanggal_mulai' => $u->tanggal_mulai?->translatedFormat('d M Y H:i'),
            'tanggal_selesai' => $u->tanggal_selesai?->translatedFormat('d M Y H:i'),
            'jumlah_soal' => $u->soals_count,
            'jumlah_sesi' => $u->sesis_count,
            'jumlah_selesai' => $u->jumlah_selesai,
            'token' => $u->token,
            'token_released' => $u->token_released_at !== null,
            'token_pengelola' => $u->token_pengelola,
            'nilai_maks' => $u->nilai_maks,
            'bobot' => $u->bobot,
        ]);

        return [
            'ujians' => $ujians,
            'penugasan' => $this->penugasan($guru),
            'filters' => [
                'guru_kelas_id' => $request->integer('guru_kelas_id') ?: null,
                'kategori' => $kategori,
                'q' => $request->string('q')->toString(),
            ],
        ];
    }

    /**
     * @return array<int, array{value: int, label: string}>
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
     * @return array<string, mixed>
     */
    private function serializeEdit(Ujian $ujian): array
    {
        return [
            'id' => $ujian->id,
            'guru_kelas_id' => $ujian->guru_kelas_id,
            'kategori' => $ujian->kategori,
            'judul' => $ujian->judul,
            'deskripsi' => $ujian->deskripsi,
            'tata_tertib' => $ujian->tata_tertib,
            'tanggal_mulai' => $ujian->tanggal_mulai?->format('Y-m-d H:i'),
            'tanggal_selesai' => $ujian->tanggal_selesai?->format('Y-m-d H:i'),
            'durasi_menit' => $ujian->durasi_menit,
            'maks_attempt' => $ujian->maks_attempt,
            'acak_soal' => $ujian->acak_soal,
            'acak_opsi' => $ujian->acak_opsi,
            'tampilkan_hasil' => $ujian->tampilkan_hasil,
            'wajib_fullscreen' => $ujian->wajib_fullscreen,
            'maks_pelanggaran' => $ujian->maks_pelanggaran,
            'nilai_maks' => $ujian->nilai_maks,
            'kkm' => $ujian->kkm ?? 75,
            'bobot' => $ujian->bobot,
            'token_pengelola' => $ujian->token_pengelola,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUjian(Request $request, Guru $guru): array
    {
        $data = $request->validate([
            'guru_kelas_id' => [
                'required',
                Rule::exists('guru_kelas', 'id')
                    ->where('guru_id', $guru->id)
                    ->where('aktif', true)
                    ->where('tahun_ajaran_id', $this->tahunAjaran?->id),
            ],
            'kategori' => ['required', Rule::in(['kuis', 'uh'])],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:10000'],
            'tata_tertib' => ['nullable', 'string', 'max:10000'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'durasi_menit' => ['required', 'integer', 'min:1', 'max:600'],
            'acak_soal' => ['boolean'],
            'acak_opsi' => ['boolean'],
            'tampilkan_hasil' => ['boolean'],
            'wajib_fullscreen' => ['boolean'],
            'nilai_maks' => ['required', 'integer', 'min:1', 'max:1000'],
            'kkm' => ['nullable', 'integer', 'min:0', 'max:100'],
            'token_pengelola' => ['sometimes', Rule::in(['guru', 'admin'])],
        ], [], [
            'guru_kelas_id' => 'Kelas & Mata Pelajaran',
            'kategori' => 'Kategori',
            'judul' => 'Judul',
            'durasi_menit' => 'Durasi',
        ]);

        $this->pastikanDalamWindow($data['kategori'], $data['tanggal_mulai'] ?? null, $data['tanggal_selesai'] ?? null);

        return $data;
    }

    /**
     * Ujian besar (uts/uas/usbk) wajib dijadwalkan di dalam window periode admin.
     */
    private function pastikanDalamWindow(string $kategori, ?string $mulai, ?string $selesai): void
    {
        if (! in_array($kategori, Ujian::KATEGORI_BESAR, true)) {
            return;
        }

        if ($mulai === null || $selesai === null) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Ujian '.strtoupper($kategori).' wajib memiliki jadwal mulai dan selesai.',
            ]);
        }

        $periode = PeriodeUjian::where('kategori', $kategori)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->where('tanggal_mulai', '<=', $mulai)
            ->where('tanggal_selesai', '>=', $selesai)
            ->exists();

        if (! $periode) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Jadwal harus berada dalam periode '.strtoupper($kategori).' yang ditetapkan admin.',
            ]);
        }
    }

    private function buatPenilaian(Ujian $ujian): void
    {
        $penilaian = Penilaian::create([
            'nama' => $this->namaPenilaian($ujian),
            'deskripsi' => $ujian->deskripsi,
            'tipe' => 'cbt',
            'nilai_maks' => $ujian->nilai_maks,
            'kkm' => $ujian->kkm ?? 75,
            'bobot' => $ujian->bobot,
            'aktif' => true,
            'sumber' => $ujian->kategori,
        ]);

        $ujian->update(['penilaian_id' => $penilaian->id]);
    }

    private function namaPenilaian(Ujian $ujian): string
    {
        return strtoupper($ujian->kategori).': '.$ujian->judul;
    }
}
