<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\BankSoal;
use App\Models\GuruKelas;
use App\Models\KelompokBankSoal;
use App\Support\HtmlSanitizer;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CBT v2: Kelompok Bank Soal — wadah bernama berisi kumpulan soal.
 * Alur: buat bank (nama via modal) -> buka bank -> isi soal.
 */
class KelompokBankSoalController extends BaseAppController
{
    public function index(Request $request): Response
    {
        $guruId = $request->user()->guru?->id;
        $matpelIds = GuruKelas::where('guru_id', $guruId)->where('aktif', true)
            ->distinct()->pluck('matpel_id')->all();

        $kelompoks = KelompokBankSoal::with(['matpel:id,name'])
            ->withCount('soals')
            ->whereIn('matpel_id', $matpelIds)
            ->orderByDesc('created_at')
            ->paginate(12);

        $matpelOptions = GuruKelas::where('guru_id', $guruId)->where('aktif', true)
            ->with('matpel:id,name')->get()
            ->pluck('matpel')->unique('id')->filter()
            ->map(fn ($m) => ['value' => $m->id, 'label' => $m->name])->values();

        return Inertia::render('guru/BankSoal/Kelompok', [
            'kelompoks' => $kelompoks,
            'matpelOptions' => $matpelOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $guruId = $request->user()->guru?->id;

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'matpel_id' => ['required', 'integer', 'exists:matpels,id'],
            'urutan_tampil' => ['required', 'in:tipe_waktu,manual'],
            'komposisi' => ['nullable', 'array'],
            'komposisi.*.max_soal' => ['nullable', 'integer', 'min:0'],
            'komposisi.*.persentase' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Validasi total persentase = 100 jika ada komposisi
        if (! empty($data['komposisi'])) {
            $total = collect($data['komposisi'])->sum('persentase');
            if ($total != 0 && $total != 100) {
                return back()->withErrors(['komposisi' => 'Total persentase harus 100%.'])->withInput();
            }
        }

        $kelompok = KelompokBankSoal::create([
            'nama' => $data['nama'],
            'kode' => $data['kode'] ?? null,
            'deskripsi' => $data['deskripsi'] ?? null,
            'matpel_id' => $data['matpel_id'],
            'guru_id' => $guruId,
            'urutan_tampil' => $data['urutan_tampil'],
            'komposisi' => $data['komposisi'] ?? null,
        ]);

        Toast::success("Bank soal \"{$kelompok->nama}\" dibuat. Silakan isi soal-soalnya.");

        return Redirect::route('app.guru.bank-soal.kelompok.show', $kelompok);
    }

    public function show(Request $request, KelompokBankSoal $kelompok): Response
    {
        $guruId = $request->user()->guru?->id;
        abort_unless($kelompok->guru_id === $guruId, 404);

        $kelompok->load(['matpel:id,name', 'soals.opsi']);

        return Inertia::render('guru/BankSoal/KelompokDetail', [
            'kelompok' => $kelompok,
        ]);
    }

    public function destroy(Request $request, KelompokBankSoal $kelompok): RedirectResponse
    {
        abort_unless($kelompok->guru_id === $request->user()->guru?->id, 404);

        $kelompok->delete();
        Toast::success('Bank soal dihapus.');

        return Redirect::route('app.guru.bank-soal.kelompok.index');
    }

    /**
     * Update nama/deskripsi bank soal.
     */
    public function update(Request $request, KelompokBankSoal $kelompok): RedirectResponse
    {
        abort_unless($kelompok->guru_id === $request->user()->guru?->id, 404);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'matpel_id' => ['required', 'exists:matpels,id'],
            'urutan_tampil' => ['required', 'in:tipe_waktu,manual'],
            'komposisi' => ['nullable', 'array'],
            'komposisi.*.max_soal' => ['nullable', 'integer', 'min:0'],
            'komposisi.*.persentase' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (! empty($data['komposisi'])) {
            $total = collect($data['komposisi'])->sum('persentase');
            if ($total != 0 && $total != 100) {
                return back()->withErrors(['komposisi' => 'Total persentase harus 100%.'])->withInput();
            }
        }

        $kelompok->update($data);
        Toast::success('Bank soal diperbarui.');

        return Redirect::back();
    }

    /**
     * Halaman khusus tambah soal ala Extraordinary CBT.
     */
    public function createSoal(Request $request, KelompokBankSoal $kelompok): Response
    {
        $guruId = $request->user()->guru?->id;
        abort_unless($kelompok->guru_id === $guruId, 404);

        $kelompok->load(['matpel:id,name']);

        return Inertia::render('guru/BankSoal/TambahSoal', [
            'kelompok' => [
                'id' => $kelompok->id,
                'nama' => $kelompok->nama,
                'kode' => $kelompok->kode,
                'matpel' => $kelompok->matpel?->name ?? '-',
            ],
            'tipeSoalList' => KelompokBankSoal::TIPE_SOAL,
        ]);
    }

    /**
     * Tambah soal ke dalam bank.
     */
    public function storeSoal(Request $request, KelompokBankSoal $kelompok): RedirectResponse
    {
        $guru = $request->user()->guru;
        abort_unless($kelompok->guru_id === $guru?->id, 404);

        $tipeList = array_keys(KelompokBankSoal::TIPE_SOAL);

        $data = $request->validate([
            'tipe' => ['required', 'in:'.implode(',', $tipeList)],
            'pertanyaan' => ['required', 'string'],
            'detail' => ['nullable', 'array'],
            'poin' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
        ]);

        $bank = BankSoal::create([
            'kelompok_bank_soal_id' => $kelompok->id,
            'matpel_id' => $kelompok->matpel_id,
            'guru_id' => $guru->id,
            'tipe' => $data['tipe'],
            'pertanyaan' => HtmlSanitizer::clean($data['pertanyaan']),
            'poin' => $data['poin'] ?? 1,
            'detail' => $data['detail'] ?? null,
        ]);

        // Simpan opsi ke tabel opsi untuk tipe pilihan ganda (kompatibilitas).
        $detail = $data['detail'] ?? [];
        if (! empty($detail['opsi']) && in_array($data['tipe'], ['pilihan_ganda', 'pilihan_ganda_kompleks', 'pilihan_ganda_citra'])) {
            $kunci = $detail['kunci'] ?? null;
            foreach ($detail['opsi'] as $i => $o) {
                $huruf = chr(97 + $i);
                $benar = is_array($kunci) ? in_array($huruf, $kunci) : ($kunci === $huruf);
                $bank->opsi()->create([
                    'teks' => HtmlSanitizer::clean($o['teks'] ?? ''),
                    'benar' => $benar,
                    'urutan' => $i + 1,
                ]);
            }
        }

        Toast::success('Soal ditambahkan ke bank.');

        return Redirect::back();
    }

    /**
     * Update soal di bank.
     */
    public function preview(KelompokBankSoal $kelompok): Response
    {
        $guru = request()->user()->guru;
        abort_unless($kelompok->guru_id === $guru?->id, 404);

        $kelompok->load(['matpel:id,name', 'soals' => fn ($q) => $q->orderBy('id')]);

        $grouped = $kelompok->soals
            ->groupBy('tipe')
            ->map(fn ($soals, $tipe) => [
                'tipe' => $tipe,
                'tipe_label' => KelompokBankSoal::TIPE_SOAL[$tipe] ?? $tipe,
                'soals' => $soals->map(fn ($s) => [
                    'id' => $s->id,
                    'tipe' => $s->tipe,
                    'tipe_label' => $s->tipe_label,
                    'pertanyaan' => $s->pertanyaan,
                    'detail' => $s->detail,
                ])->values(),
            ])->values();

        return Inertia::render('guru/BankSoal/Preview', [
            'kelompok' => [
                'id' => $kelompok->id,
                'nama' => $kelompok->nama,
                'kode' => $kelompok->kode,
                'matpel' => $kelompok->matpel?->name ?? '-',
            ],
            'grouped' => $grouped,
        ]);
    }

    public function editSoal(KelompokBankSoal $kelompok, BankSoal $soal): Response
    {
        $guru = request()->user()->guru;
        abort_unless($kelompok->guru_id === $guru?->id && $soal->kelompok_bank_soal_id === $kelompok->id, 404);

        $soal->load('opsi');

        return Inertia::render('guru/BankSoal/EditSoal', [
            'kelompok' => [
                'id' => $kelompok->id,
                'nama' => $kelompok->nama,
                'kode' => $kelompok->kode,
                'matpel' => $kelompok->matpel?->name ?? '-',
            ],
            'soal' => [
                'id' => $soal->id,
                'tipe' => $soal->tipe,
                'pertanyaan' => $soal->pertanyaan,
                'detail' => $soal->detail,
            ],
            'tipeSoalList' => KelompokBankSoal::TIPE_SOAL,
        ]);
    }

    public function updateSoal(Request $request, KelompokBankSoal $kelompok, BankSoal $soal): RedirectResponse
    {
        $guru = $request->user()->guru;
        abort_unless($kelompok->guru_id === $guru?->id && $soal->kelompok_bank_soal_id === $kelompok->id, 404);

        $tipeList = array_keys(KelompokBankSoal::TIPE_SOAL);

        $data = $request->validate([
            'tipe' => ['required', 'in:'.implode(',', $tipeList)],
            'pertanyaan' => ['required', 'string'],
            'detail' => ['nullable', 'array'],
            'poin' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
        ]);

        $soal->update([
            'tipe' => $data['tipe'],
            'pertanyaan' => HtmlSanitizer::clean($data['pertanyaan']),
            'detail' => $data['detail'] ?? null,
            'poin' => (int) ($data['poin'] ?? $soal->poin),
        ]);

        // Hapus opsi lama, simpan yang baru untuk tipe pilihan ganda.
        $soal->opsi()->delete();
        $detail = $data['detail'] ?? [];
        if (! empty($detail['opsi']) && in_array($data['tipe'], ['pilihan_ganda', 'pilihan_ganda_kompleks', 'pilihan_ganda_citra'])) {
            $kunci = $detail['kunci'] ?? null;
            foreach ($detail['opsi'] as $i => $o) {
                $huruf = chr(97 + $i);
                $benar = is_array($kunci) ? in_array($huruf, $kunci) : ($kunci === $huruf);
                $soal->opsi()->create([
                    'teks' => HtmlSanitizer::clean($o['teks'] ?? ''),
                    'benar' => $benar,
                    'urutan' => $i + 1,
                ]);
            }
        }

        Toast::success('Soal diperbarui.');

        return Redirect::route('app.guru.bank-soal.kelompok.show', $kelompok);
    }

    /**
     * Hapus soal dari bank.
     */
    public function destroySoal(Request $request, KelompokBankSoal $kelompok, BankSoal $soal): RedirectResponse
    {
        $guru = $request->user()->guru;
        abort_unless($kelompok->guru_id === $guru?->id && $soal->kelompok_bank_soal_id === $kelompok->id, 404);

        $soal->opsi()->delete();
        $soal->delete();

        Toast::success('Soal dihapus dari bank.');

        return Redirect::back();
    }
}
