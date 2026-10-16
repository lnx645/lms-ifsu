<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Matpel;
use App\Models\Soal;
use App\Support\HtmlSanitizer;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BankSoalController extends BaseAppController implements HasMiddleware
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
     * Daftar bank soal untuk matpel yang diampu guru.
     */
    public function index(Request $request): Response
    {
        $guru = $request->user()->guru;
        $matpelIds = $this->matpelIds($guru);

        $bank = BankSoal::query()
            ->with(['matpel:id,name', 'opsi:id,bank_soal_id,teks,benar'])
            ->untukMatpel($matpelIds)
            ->when($request->integer('matpel_id'), fn ($q, $id) => $q->where('matpel_id', $id))
            ->when($request->input('tipe'), fn ($q, $t) => $q->where('tipe', is_array($t) ? ($t['value'] ?? $t[0] ?? null) : $t))
            ->when($request->string('kesulitan')->toString(), fn ($q, $k) => $q->where('kesulitan', $k))
            ->when($request->string('topik')->toString(), fn ($q, $t) => $q->where('topik', 'like', "%{$t}%"))
            ->when($request->string('q')->toString(), fn ($q, $kw) => $q->where('pertanyaan', 'like', "%{$kw}%"))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $bank->through(fn (BankSoal $b): array => [
            'id' => $b->id,
            'matpel_id' => $b->matpel_id,
            'matpel' => $b->matpel?->name,
            'tipe' => $b->tipe,
            'pertanyaan' => $b->pertanyaan,
            'poin' => $b->poin,
            'topik' => $b->topik,
            'kesulitan' => $b->kesulitan,
            'kunci_isian' => $b->kunci_isian,
            'isian_case_sensitive' => $b->isian_case_sensitive,
            'jumlah_opsi' => $b->opsi->count(),
            'opsi' => $b->opsi->map(fn ($o): array => [
                'id' => $o->id,
                'teks' => $o->teks,
                'benar' => $o->benar,
            ])->values(),
            'milik_saya' => $b->guru_id === $this->guruId($b),
        ]);

        return Inertia::render('guru/BankSoal/Index', [
            'bank' => $bank,
            'matpelOptions' => $this->matpelOptions($guru),
            'filters' => [
                'matpel_id' => $request->integer('matpel_id') ?: null,
                'tipe' => $request->string('tipe')->toString() ?: null,
                'kesulitan' => $request->string('kesulitan')->toString() ?: null,
                'topik' => $request->string('topik')->toString(),
                'q' => $request->string('q')->toString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $guru = $request->user()->guru;
        $data = $this->validateBankSoal($request, $guru);

        DB::transaction(function () use ($data, $guru): void {
            $bank = BankSoal::create([
                'matpel_id' => $data['matpel_id'],
                'guru_id' => $guru->id,
                'tipe' => $data['tipe'],
                'pertanyaan' => HtmlSanitizer::clean($data['pertanyaan']),
                'poin' => $data['poin'],
                'topik' => $data['topik'] ?? null,
                'kesulitan' => $data['kesulitan'],
                'kunci_isian' => $this->kunciIsian($data),
                'isian_case_sensitive' => $data['isian_case_sensitive'] ?? false,
            ]);

            $this->simpanOpsi($bank, $data);
        });

        Toast::success('Soal ditambahkan ke bank.');

        return Redirect::back();
    }

    public function update(Request $request, BankSoal $bankSoal): RedirectResponse
    {
        $guru = $request->user()->guru;
        $this->pastikanBoleh($bankSoal, $guru);

        $data = $this->validateBankSoal($request, $guru);

        DB::transaction(function () use ($bankSoal, $data): void {
            $bankSoal->update([
                'matpel_id' => $data['matpel_id'],
                'tipe' => $data['tipe'],
                'pertanyaan' => HtmlSanitizer::clean($data['pertanyaan']),
                'poin' => $data['poin'],
                'topik' => $data['topik'] ?? null,
                'kesulitan' => $data['kesulitan'],
                'kunci_isian' => $this->kunciIsian($data),
                'isian_case_sensitive' => $data['isian_case_sensitive'] ?? false,
            ]);

            $bankSoal->opsi()->delete();
            $this->simpanOpsi($bankSoal, $data);
        });

        Toast::success('Soal bank diperbarui.');

        return Redirect::back();
    }

    public function destroy(Request $request, BankSoal $bankSoal): RedirectResponse
    {
        $guru = $request->user()->guru;
        $this->pastikanBoleh($bankSoal, $guru);

        $bankSoal->delete();

        Toast::success('Soal bank dihapus.');

        return Redirect::back();
    }

    /**
     * @return array<int, int>
     */
    private function matpelIds(Guru $guru): array
    {
        return GuruKelas::where('guru_id', $guru->id)
            ->where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->distinct()
            ->pluck('matpel_id')
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function matpelOptions(Guru $guru): array
    {
        return Matpel::query()
            ->whereIn('id', $this->matpelIds($guru))
            ->orderBy('name')
            ->get()
            ->map(fn (Matpel $m): array => ['value' => $m->id, 'label' => $m->name])
            ->all();
    }

    private function guruId(BankSoal $bank): ?int
    {
        return $bank->guru_id;
    }

    /**
     * Guru hanya boleh mengubah soal bank pada matpel yang diampu.
     */
    private function pastikanBoleh(BankSoal $bankSoal, Guru $guru): void
    {
        abort_unless(in_array($bankSoal->matpel_id, $this->matpelIds($guru), true), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBankSoal(Request $request, Guru $guru): array
    {
        $data = $request->validate([
            'matpel_id' => ['required', Rule::in($this->matpelIds($guru))],
            'tipe' => ['required', Rule::in(Soal::TIPE)],
            'pertanyaan' => ['required', 'string', 'max:20000'],
            'poin' => ['required', 'integer', 'min:1', 'max:100'],
            'topik' => ['nullable', 'string', 'max:100'],
            'kesulitan' => ['required', Rule::in(BankSoal::KESULITAN)],
            'opsi' => ['array'],
            'opsi.*.teks' => ['nullable', 'string', 'max:2000'],
            'opsi.*.benar' => ['boolean'],
            'kunci_isian' => ['array'],
            'kunci_isian.*' => ['nullable', 'string', 'max:255'],
            'isian_case_sensitive' => ['boolean'],
        ], [], [
            'matpel_id' => 'Mata Pelajaran',
            'tipe' => 'Tipe Soal',
            'pertanyaan' => 'Pertanyaan',
        ]);

        $this->validateStruktur($data);

        return $data;
    }

    /**
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
    private function simpanOpsi(BankSoal $bank, array $data): void
    {
        if (! $bank->butuhOpsi()) {
            return;
        }

        foreach (array_values($data['opsi'] ?? []) as $i => $opsi) {
            $bank->opsi()->create([
                'teks' => $opsi['teks'],
                'benar' => (bool) ($opsi['benar'] ?? false),
                'urutan' => $i,
            ]);
        }
    }
}
