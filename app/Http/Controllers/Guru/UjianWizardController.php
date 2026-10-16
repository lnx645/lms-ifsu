<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\GuruKelas;
use App\Models\KelompokBankSoal;
use App\Models\Ujian;
use App\Services\KelengkapanUjianService;
use App\Services\NotifikasiService;
use App\Services\PaketSoalService;
use App\Services\UjianService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CBT v2: Wizard pembuatan ujian terpandu.
 *
 * - Mode santai (kuis/uh): 3 langkah (info → soal → review).
 * - Mode resmi (uts/uas/usbk): 5 langkah (info → paket → sesi → token → review).
 *
 * Ujian dibuat sebagai draft di langkah 1, tiap langkah memperbarui draft.
 */
class UjianWizardController extends BaseAppController
{
    public function __construct(
        private readonly UjianService $ujianService,
        private readonly PaketSoalService $paketSoalService,
        private readonly KelengkapanUjianService $kelengkapanService,
    ) {}

    /**
     * Langkah 1: pilih mode & info dasar.
     */
    public function create(Request $request): Response
    {
        $guruId = $request->user()->guru?->id;

        $penugasan = GuruKelas::where('guru_id', $guruId)
            ->where('aktif', true)
            ->with(['kelas', 'matpel'])
            ->get()
            ->map(fn ($gk) => [
                'value' => $gk->id,
                'label' => ($gk->kelas?->nama ?? '-').' — '.($gk->matpel?->name ?? '-'),
            ]);

        return Inertia::render('guru/Ujian/Wizard', [
            'penugasan' => $penugasan,
            'mode' => $request->query('mode', 'santai'),
        ]);
    }

    /**
     * Simpan langkah 1: buat draft ujian.
     */
    public function storeInfo(Request $request): RedirectResponse
    {
        $guruId = $request->user()->guru?->id;

        $data = $request->validate([
            'mode' => ['required', 'in:santai,resmi'],
            'guru_kelas_id' => ['required', 'integer'],
            'kategori' => ['required', 'string'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'durasi_menit' => ['required', 'integer', 'min:5', 'max:600'],
        ]);

        $penugasan = GuruKelas::where('id', $data['guru_kelas_id'])
            ->where('guru_id', $guruId)->where('aktif', true)->firstOrFail();

        // Validasi kategori sesuai mode.
        // Ujian resmi (uts/uas/usbk) HANYA dibuat panitia, guru diblokir total.
        $kategoriBesar = in_array($data['kategori'], Ujian::KATEGORI_BESAR, true);
        if ($kategoriBesar) {
            abort(403, 'Ujian resmi (UTS/UAS/USBK) hanya dapat dibuat oleh panitia.');
        }
        if ($data['mode'] === 'santai' && $kategoriBesar) {
            $data['kategori'] = 'kuis';
        }

        $defaults = $this->ujianService->defaultKategori($data['kategori']);

        $ujian = Ujian::create(array_merge($defaults, [
            'guru_kelas_id' => $penugasan->id,
            'guru_id' => $guruId,
            'kategori' => $data['kategori'],
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'durasi_menit' => $data['durasi_menit'],
            'status' => 'draft',
        ]));

        // Tata tertib default sesuai mode.
        if ($data['mode'] === 'santai') {
            $ujian->update(['tata_tertib' => "1. Kerjakan dengan jujur.\n2. Dilarang bekerja sama.\n3. Perhatikan sisa waktu."]);
        }

        Toast::success('Langkah 1 selesai. Lanjut ke langkah berikutnya.');

        return Redirect::route('app.guru.ujian.wizard.langkah', [
            'ujian' => $ujian->id, 'langkah' => 2, 'mode' => $data['mode'],
        ]);
    }

    /**
     * Tampilkan langkah wizard. Mode diturunkan dari kategori ujian (anti-tamper via URL).
     *
     * CBT v2 (sekolah beneran):
     * - Guru mode resmi: 3 langkah (info → paket → serahkan ke panitia).
     * - Panitia yang atur sesi/ruang/jadwal/token lalu menerbitkan.
     */
    public function langkah(Request $request, Ujian $ujian, int $langkah): Response|RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        // Mode resmi = kategori besar (uts/uas/usbk). Jangan percaya query param.
        $mode = $ujian->kategoriBesar() ? 'resmi' : 'santai';
        // Guru resmi: 3 langkah (info, paket, serahkan). Santai: 3 langkah (info, soal, terbit).
        $maksLangkah = 3;

        if ($langkah < 2 || $langkah > $maksLangkah) {
            return Redirect::route('app.guru.ujian.index');
        }

        $ujian->load(['paketSoals.soals', 'sesis.ruang', 'soals']);

        $props = [
            'ujian' => $ujian,
            'mode' => $mode,
            'langkah' => $langkah,
            'maksLangkah' => $maksLangkah,
            'kelengkapan' => $this->kelengkapanService->cekGuru($ujian),
        ];

        // Data tambahan per langkah.
        if ($mode === 'resmi' && $langkah === 2) {
            $matpelId = $ujian->guruKelas?->matpel_id;
            $guruId = $request->user()->guru?->id;
            $props['kelompokBanks'] = KelompokBankSoal::withCount('soals')
                ->where('matpel_id', $matpelId)
                ->where('guru_id', $guruId)
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn ($k) => [
                    'id' => $k->id,
                    'nama' => $k->nama,
                    'soals_count' => $k->soals_count,
                ]);
        }

        return Inertia::render('guru/Ujian/WizardLangkah', $props);
    }

    /**
     * Generate paket soal dari bank terpilih (langkah 2 mode resmi).
     */
    public function generatePaket(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $data = $request->validate([
            'jumlah_paket' => ['required', 'integer', 'min:1', 'max:10'],
            'soal_per_paket' => ['required', 'integer', 'min:1', 'max:100'],
            'kelompok_bank_soal_id' => ['required', 'integer', 'exists:cbt_kelompok_bank_soals,id'],
        ]);

        $kelompok = KelompokBankSoal::findOrFail($data['kelompok_bank_soal_id']);
        abort_unless($kelompok->guru_id === $request->user()->guru?->id, 404);

        $bankSoalIds = $kelompok->soals()->pluck('id')->all();

        try {
            $pakets = $this->paketSoalService->generateOtomatis(
                $ujian, $data['jumlah_paket'], $data['soal_per_paket'], $bankSoalIds
            );
        } catch (\InvalidArgumentException $e) {
            Toast::error($e->getMessage());

            return Redirect::back();
        }

        Toast::success(count($pakets).' paket soal berhasil dibuat dari "'.$kelompok->nama.'".');

        return Redirect::route('app.guru.ujian.wizard.langkah', [
            'ujian' => $ujian->id, 'langkah' => 3, 'mode' => 'resmi',
        ]);
    }

    /**
     * Serahkan ke panitia (langkah terakhir guru mode resmi).
     * Guru selesai sampai paket soal; panitia yang atur sesi/jadwal/token & menerbitkan.
     */
    public function serahkan(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        if (! $ujian->kategoriBesar()) {
            Toast::error('Hanya ujian resmi yang diserahkan ke panitia.');

            return Redirect::back();
        }

        if (! $ujian->memakaiPaketSoal()) {
            Toast::error('Buat paket soal dulu sebelum diserahkan ke panitia.');

            return Redirect::back();
        }

        $ujian->update(['status' => 'diajukan']);
        Toast::success('Ujian diserahkan ke panitia. Panitia akan mengatur jadwal, sesi, dan token.');

        return Redirect::route('app.guru.ujian.index');
    }

    /**
     * Terbitkan dari wizard (mode santai saja; resmi diterbitkan panitia).
     */
    public function terbit(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        // Ujian resmi (UTS/UAS/USBK) hanya bisa diterbitkan panitia, guru hanya serahkan
        if ($ujian->kategoriBesar()) {
            abort(403, 'Ujian resmi hanya dapat diterbitkan oleh panitia.');
        }

        $masalah = $this->kelengkapanService->cekGuru($ujian);
        if ($masalah !== []) {
            Toast::error('Belum bisa diterbitkan: '.implode(' ', $masalah));

            return Redirect::back();
        }

        $ujian->update(['status' => 'terbit']);
        app(NotifikasiService::class)->jadwalUjian($ujian->fresh());
        Toast::success('Ujian diterbitkan!');

        return Redirect::route('app.guru.ujian.index');
    }
}
