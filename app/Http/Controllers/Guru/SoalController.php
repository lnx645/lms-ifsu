<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\BankSoal;
use App\Models\KelompokBankSoal;
use App\Models\OpsiSoal;
use App\Models\Soal;
use App\Models\Ujian;
use App\Services\BankSoalService;
use App\Support\HtmlSanitizer;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SoalController extends BaseAppController implements HasMiddleware
{
    public function __construct(private readonly BankSoalService $bankSoalService)
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
     * Halaman bank soal untuk satu ujian.
     */
    public function index(Request $request, Ujian $ujian): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        return Inertia::render('guru/Ujian/Soal', $this->soalData($ujian));
    }

    public function store(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $data = $this->validateSoal($request, $ujian);

        DB::transaction(function () use ($ujian, $data): void {
            $urutan = (int) $ujian->soals()->where('paket_soal_id', $data['paket_soal_id'] ?? null)->max('urutan') + 1;

            $soal = $ujian->soals()->create([
                'tipe' => $data['tipe'],
                'pertanyaan' => HtmlSanitizer::clean($data['pertanyaan']),
                'poin' => $data['poin'],
                'urutan' => $urutan,
                'paket_soal_id' => $data['paket_soal_id'] ?? null,
                'kunci_isian' => $this->kunciIsian($data),
                'isian_case_sensitive' => $data['isian_case_sensitive'] ?? false,
                'kode_kd' => $data['kode_kd'] ?? null,
                'indikator' => $data['indikator'] ?? null,
            ]);

            $this->simpanOpsi($soal, $data);
        });

        Toast::success('Soal berhasil ditambahkan.');

        return Redirect::back();
    }

    public function update(Request $request, Ujian $ujian, Soal $soal): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($soal->ujian_id === $ujian->id, 404);

        // Jangan ubah soal yang ujiannya sudah dikerjakan siswa: opsi yang
        // dihapus/dibuat ulang akan merusak referensi JawabanSiswa.opsi_dipilih.
        if ($ujian->pengerjaans()->exists()) {
            Toast::error('Soal tidak dapat diubah karena ujian sudah dikerjakan siswa.');

            return Redirect::back();
        }

        $data = $this->validateSoal($request, $ujian);

        DB::transaction(function () use ($soal, $data): void {
            $soal->update([
                'tipe' => $data['tipe'],
                'pertanyaan' => HtmlSanitizer::clean($data['pertanyaan']),
                'poin' => $data['poin'],
                'kunci_isian' => $this->kunciIsian($data),
                'isian_case_sensitive' => $data['isian_case_sensitive'] ?? false,
                'kode_kd' => $data['kode_kd'] ?? null,
                'indikator' => $data['indikator'] ?? null,
            ]);

            $soal->opsi()->delete();
            $this->simpanOpsi($soal, $data);
        });

        Toast::success('Soal berhasil diperbarui.');

        return Redirect::back();
    }

    public function destroy(Request $request, Ujian $ujian, Soal $soal): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($soal->ujian_id === $ujian->id, 404);

        if ($ujian->pengerjaans()->exists()) {
            Toast::error('Soal tidak dapat dihapus karena ujian sudah dikerjakan siswa.');

            return Redirect::back();
        }

        $soal->delete();

        Toast::success('Soal dihapus.');

        return Redirect::back();
    }

    /**
     * Ambil (salin) soal terpilih dari bank ke ujian.
     */
    public function ambilDariBank(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $matpelId = $ujian->guruKelas->matpel_id;
        $guruId = $request->user()->guru?->id;

        $data = $request->validate([
            'bank_soal_ids' => ['required', 'array', 'min:1'],
            'bank_soal_ids.*' => [Rule::exists('cbt_bank_soals', 'id')->where('matpel_id', $matpelId)->where('guru_id', $guruId)],
        ]);

        $jumlah = $this->bankSoalService->salinKeUjian($ujian, $data['bank_soal_ids']);

        Toast::success($jumlah.' soal disalin dari bank.');

        return Redirect::back();
    }

    /**
     * Simpan satu soal ujian ke bank.
     */
    public function simpanKeBank(Request $request, Ujian $ujian, Soal $soal): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($soal->ujian_id === $ujian->id, 404);

        $data = $request->validate([
            'topik' => ['nullable', 'string', 'max:100'],
            'kesulitan' => ['required', Rule::in(BankSoal::KESULITAN)],
        ]);

        $this->bankSoalService->simpanDariSoal(
            $soal,
            $ujian->guruKelas->matpel_id,
            $request->user()->guru?->id,
            ['topik' => $data['topik'] ?? null, 'kesulitan' => $data['kesulitan']],
        );

        Toast::success('Soal disimpan ke bank.');

        return Redirect::back();
    }

    /**
     * Generate soal acak dari bank sesuai kriteria lalu salin ke ujian.
     */
    public function generateDariBank(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $matpelId = $ujian->guruKelas->matpel_id;

        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1', 'max:100'],
            'tipe' => ['nullable', Rule::in(Soal::TIPE)],
            'topik' => ['nullable', 'string', 'max:100'],
            'kesulitan' => ['nullable', Rule::in(BankSoal::KESULITAN)],
        ]);

        $jumlah = $this->bankSoalService->generate($ujian, [
            'matpel_id' => $matpelId,
            'guru_id' => $request->user()->guru?->id,
            'jumlah' => $data['jumlah'],
            'tipe' => $data['tipe'] ?? null,
            'topik' => $data['topik'] ?? null,
            'kesulitan' => $data['kesulitan'] ?? null,
        ]);

        if ($jumlah === 0) {
            Toast::warning('Tidak ada soal bank yang cocok dengan kriteria.');
        } else {
            Toast::success($jumlah.' soal acak ditambahkan dari bank.');
        }

        return Redirect::back();
    }

    /**
     * @return array<string, mixed>
     */
    private function soalData(Ujian $ujian): array
    {
        $ujian->load(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'soals.opsi', 'paketSoals']);

        $matpelId = $ujian->guruKelas?->matpel_id;

        $bankKelompok = KelompokBankSoal::query()
            ->where('matpel_id', $matpelId)
            ->where('guru_id', $ujian->guru_id)
            ->withCount('soals')
            ->orderBy('nama')
            ->get()
            ->map(fn (KelompokBankSoal $k): array => [
                'id' => $k->id,
                'nama' => $k->nama,
                'jumlah' => $k->soals_count,
            ]);

        $bank = BankSoal::query()
            ->with('opsi:id,bank_soal_id,teks,benar')
            ->where('matpel_id', $matpelId)
            ->where('guru_id', $ujian->guru_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (BankSoal $b): array => [
                'id' => $b->id,
                'tipe' => $b->tipe,
                'pertanyaan' => $b->pertanyaan,
                'poin' => $b->poin,
                'topik' => $b->topik,
                'kesulitan' => $b->kesulitan,
                'jumlah_opsi' => $b->opsi->count(),
                'kelompok_id' => $b->kelompok_bank_soal_id,
            ]);

        $mapSoal = fn (Soal $s): array => [
            'id' => $s->id,
            'tipe' => $s->tipe,
            'pertanyaan' => $s->pertanyaan,
            'poin' => $s->poin,
            'urutan' => $s->urutan,
            'paket_soal_id' => $s->paket_soal_id,
            'kunci_isian' => $s->kunci_isian,
            'isian_case_sensitive' => $s->isian_case_sensitive,
            'kode_kd' => $s->kode_kd,
            'indikator' => $s->indikator,
            'media_path' => $s->media_path,
            'media_type' => $s->media_type,
            'media_url' => $s->media_path ? Storage::url($s->media_path) : null,
            'detail' => $s->detail,
            'opsi' => $s->opsi->map(fn (OpsiSoal $o): array => [
                'id' => $o->id,
                'teks' => $o->teks,
                'benar' => $o->benar,
            ])->values(),
        ];

        return [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'status' => $ujian->status,
                'kelas' => $ujian->guruKelas?->kelas?->nama,
                'matpel' => $ujian->guruKelas?->matpel?->name,
                'matpel_id' => $ujian->guruKelas?->matpel_id,
                'nilai_maks' => $ujian->nilai_maks,
                'total_poin' => (int) $ujian->soals->sum('poin'),
                'memakai_paket' => $ujian->memakaiPaketSoal(),
            ],
            'pakets' => $ujian->paketSoals->map(fn ($p) => [
                'id' => $p->id,
                'kode' => $p->kode,
                'nama' => $p->nama,
                'soals' => $ujian->soals->where('paket_soal_id', $p->id)->map($mapSoal)->values(),
            ])->values(),
            'soals' => $ujian->soals->whereNull('paket_soal_id')->map($mapSoal)->values(),
            'bank' => $bank,
            'bankKelompok' => $bankKelompok,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSoal(Request $request, ?Ujian $ujian = null): array
    {
        // Mapping kode legacy frontend ke tipe modern
        $legacyMap = [
            'pg' => 'pilihan_ganda',
            'multi' => 'pilihan_ganda_kompleks',
            'isian' => 'isian_singkat',
            'esai' => 'uraian',
        ];
        if (isset($legacyMap[$request->input('tipe')])) {
            $request->merge(['tipe' => $legacyMap[$request->input('tipe')]]);
        }

        $data = $request->validate([
            'tipe' => ['required', Rule::in(Soal::TIPE)],
            'pertanyaan' => ['required', 'string', 'max:20000'],
            'poin' => ['required', 'integer', 'min:1', 'max:100'],
            'paket_soal_id' => [
                'nullable', 'integer',
                $ujian ? Rule::exists('cbt_paket_soals', 'id')->where('ujian_id', $ujian->id) : 'exists:cbt_paket_soals,id',
            ],
            'opsi' => ['array'],
            'opsi.*.teks' => ['nullable', 'string', 'max:2000'],
            'opsi.*.benar' => ['boolean'],
            'kunci_isian' => ['array'],
            'kunci_isian.*' => ['nullable', 'string', 'max:255'],
            'isian_case_sensitive' => ['boolean'],
            'kode_kd' => ['nullable', 'string', 'max:50'],
            'indikator' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateStruktur($data);

        return $data;
    }

    /**
     * Validasi struktur opsi/kunci sesuai tipe soal.
     *
     * @param  array<string, mixed>  $data
     */
    private function validateStruktur(array $data): void
    {
        $tipe = $data['tipe'];
        $opsi = collect($data['opsi'] ?? []);
        $benar = $opsi->filter(fn ($o): bool => (bool) ($o['benar'] ?? false));

        if (in_array($tipe, ['pg', 'benar_salah', 'multi'], true)) {
            if ($opsi->count() < 2) {
                throw ValidationException::withMessages(['opsi' => 'Sediakan minimal 2 opsi jawaban.']);
            }

            if ($opsi->contains(fn ($o): bool => trim((string) ($o['teks'] ?? '')) === '')) {
                throw ValidationException::withMessages(['opsi' => 'Semua opsi jawaban harus diisi.']);
            }

            if (in_array($tipe, ['pg', 'benar_salah'], true) && $benar->count() !== 1) {
                throw ValidationException::withMessages(['opsi' => 'Tandai tepat satu opsi sebagai jawaban benar.']);
            }

            if ($tipe === 'multi' && $benar->count() < 1) {
                throw ValidationException::withMessages(['opsi' => 'Tandai minimal satu opsi sebagai jawaban benar.']);
            }
        } elseif ($tipe === 'isian') {
            $kunci = collect($data['kunci_isian'] ?? [])->filter(fn ($k): bool => trim((string) $k) !== '');
            if ($kunci->isEmpty()) {
                throw ValidationException::withMessages(['kunci_isian' => 'Isi minimal satu kunci jawaban.']);
            }
        }
    }

    /**
     * Normalisasi kunci jawaban isian: trim & buang yang kosong.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>|null
     */
    private function kunciIsian(array $data): ?array
    {
        if (($data['tipe'] ?? null) !== 'isian') {
            return null;
        }

        return array_values(array_filter(
            array_map(fn ($k): string => trim((string) $k), $data['kunci_isian'] ?? []),
            fn (string $k): bool => $k !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpanOpsi(Soal $soal, array $data): void
    {
        if (! $soal->butuhOpsi()) {
            return;
        }

        foreach (array_values($data['opsi'] ?? []) as $i => $opsi) {
            $soal->opsi()->create([
                'teks' => $opsi['teks'],
                'benar' => (bool) ($opsi['benar'] ?? false),
                'urutan' => $i,
            ]);
        }
    }

    /**
     * Unggah lampiran media (gambar/audio) untuk soal.
     */
    public function uploadMedia(Request $request, Ujian $ujian, Soal $soal): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($soal->ujian_id === $ujian->id, 404);

        $data = $request->validate([
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp3,ogg,wav', 'max:10240'],
        ]);

        $file = $data['media'];
        $mime = $file->getMimeType();
        $tipe = str_starts_with($mime, 'image/') ? 'gambar' : 'audio';

        if ($soal->media_path) {
            Storage::disk('public')->delete($soal->media_path);
        }

        $path = $file->store('soal-media', 'public');
        $soal->update(['media_path' => $path, 'media_type' => $tipe]);

        Toast::success('Media soal diunggah.');

        return Redirect::back();
    }

    /**
     * Hapus lampiran media soal.
     */
    public function hapusMedia(Request $request, Ujian $ujian, Soal $soal): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($soal->ujian_id === $ujian->id, 404);

        if ($soal->media_path) {
            Storage::disk('public')->delete($soal->media_path);
        }

        $soal->update(['media_path' => null, 'media_type' => null]);
        Toast::success('Media soal dihapus.');

        return Redirect::back();
    }
}
