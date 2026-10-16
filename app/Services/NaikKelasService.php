<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NaikKelasService
{
    /**
     * Preview pemetaan kenaikan kelas dari TA sumber.
     *
     * @return array{sumber: array<string, mixed>, target: array<string, mixed>, kelas: array<int, array<string, mixed>>, ringkasan: array<string, int>, kelas_tujuan: array<string, array<int, array<string, mixed>>>}
     */
    public function preview(TahunAjaran $sumber, TahunAjaran $target): array
    {
        $assignments = SiswaKelas::query()
            ->where('tahun_ajaran_id', $sumber->id)
            ->where('active', true)
            ->whereIn('kelas_id', Kelas::leaf()->select('id'))
            ->with(['kelas.parent.parent', 'siswa'])
            ->get()
            ->groupBy('kelas_id');

        $plan = $this->kelasPlan($assignments->keys());

        $kelas = [];
        $ringkasan = ['naik' => 0, 'tinggal' => 0, 'lulus' => 0];

        foreach ($assignments as $kelasId => $items) {
            /** @var SiswaKelas $first */
            $first = $items->first();
            $tingkat = $plan[$kelasId]['tingkat'];
            $targetModel = $plan[$kelasId]['target'];

            if ($targetModel) {
                $defaultStatus = 'naik';
                $ringkasan['naik'] += $items->count();
            } elseif ($tingkat === 'XII') {
                $defaultStatus = 'lulus';
                $ringkasan['lulus'] += $items->count();
            } else {
                $defaultStatus = 'tinggal';
                $ringkasan['tinggal'] += $items->count();
            }

            $kelas[] = [
                'kelas_asal' => $first->kelas->nama,
                'kelas_target' => $targetModel?->nama ?? ($tingkat === 'XII' ? 'LULUS' : null),
                'kelas_target_id' => $targetModel?->id,
                'tingkat' => $tingkat,
                'siswa' => $items->map(fn (SiswaKelas $assignment) => [
                    'nisn' => $assignment->siswa_nisn,
                    'nama' => $assignment->siswa?->nama_lengkap,
                    'status' => $defaultStatus,
                ])->values()->all(),
            ];
        }

        usort($kelas, fn (array $a, array $b): int => strcmp($a['kelas_asal'], $b['kelas_asal']));

        return [
            'sumber' => ['id' => $sumber->id, 'name' => $sumber->name],
            'target' => ['id' => $target->id, 'name' => $target->name],
            'kelas' => $kelas,
            'ringkasan' => $ringkasan,
            'kelas_tujuan' => $this->kelasTujuanOptions(),
        ];
    }

    /**
     * Daftar kelas tujuan untuk setiap tingkat sumber, dipakai untuk
     * memindahkan siswa ke kelas lain saat naik kelas (mis. X -> XI).
     * Kunci array = tingkat sumber, isi = kelas rombel tingkat berikutnya.
     *
     * @return array<string, array<int, array{value: int, label: string, jurusan: string}>>
     */
    private function kelasTujuanOptions(): array
    {
        $options = [];

        Kelas::leaf()
            ->with(['parent.parent', 'jurusan'])
            ->get()
            ->groupBy(fn (Kelas $kelas): ?string => $kelas->tingkatSekarang())
            ->each(function (Collection $group, ?string $tingkat) use (&$options): void {
                $sumberTingkat = match ($tingkat) {
                    'XI' => 'X',
                    'XII' => 'XI',
                    default => null,
                };

                if ($sumberTingkat === null) {
                    return;
                }

                $options[$sumberTingkat] = $group
                    ->sortBy(fn (Kelas $kelas): string => ($kelas->jurusan?->name ?? '~').' '.$kelas->nama)
                    ->values()
                    ->map(fn (Kelas $kelas): array => [
                        'value' => $kelas->id,
                        'label' => $kelas->nama,
                        'jurusan' => $kelas->jurusan?->name ?? '',
                    ])
                    ->all();
            });

        return $options;
    }

    /**
     * Eksekusi kenaikan kelas dalam satu transaksi.
     *
     * @param  array<int, array{nisn: string, status: string, kelas_target?: int|null}>  $pilihan
     * @return array{naik: int, tinggal: int, lulus: int}
     */
    public function execute(TahunAjaran $sumber, TahunAjaran $target, array $pilihan): array
    {
        return DB::transaction(function () use ($sumber, $target, $pilihan): array {
            $hasil = ['naik' => 0, 'tinggal' => 0, 'lulus' => 0];

            $nisns = collect($pilihan)->pluck('nisn')->unique()->values()->all();

            $assignments = SiswaKelas::query()
                ->where('tahun_ajaran_id', $sumber->id)
                ->where('active', true)
                ->whereIn('siswa_nisn', $nisns)
                ->whereIn('kelas_id', Kelas::leaf()->select('id'))
                ->with(['kelas.parent.parent'])
                ->orderBy('id')
                ->get()
                ->groupBy('siswa_nisn')
                ->map->first();

            $plan = $this->kelasPlan($assignments->pluck('kelas_id')->unique());

            $kelasTargetIds = collect($pilihan)
                ->filter(fn (array $item): bool => $item['status'] === 'naik')
                ->pluck('kelas_target')
                ->filter(fn ($id): bool => $id !== null && $id !== '')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $kelasTargets = Kelas::query()
                ->whereIn('id', $kelasTargetIds)
                ->with(['parent.parent'])
                ->get()
                ->keyBy('id');

            foreach ($pilihan as $item) {
                $nisn = $item['nisn'];
                $status = $item['status'];

                $assignment = $assignments->get($nisn);

                if (! $assignment) {
                    continue;
                }

                $kelas = $assignment->kelas;

                if ($status === 'lulus') {
                    Siswa::where('nisn', $nisn)->update(['status' => 'lulus']);
                    $assignment->update(['active' => false]);
                    $hasil['lulus']++;

                    continue;
                }

                if ($status === 'naik') {
                    $targetKelas = $this->resolveTargetKelas(
                        $kelas,
                        $item['kelas_target'] ?? null,
                        $kelasTargets,
                        $plan[$kelas->id]['target'],
                    );
                } else {
                    $targetKelas = $kelas;
                }

                SiswaKelas::updateOrCreate(
                    [
                        'siswa_nisn' => $nisn,
                        'kelas_id' => $targetKelas->id,
                        'tahun_ajaran_id' => $target->id,
                    ],
                    [
                        'active' => true,
                        'pertama_masuk' => false,
                    ],
                );

                $assignment->update(['active' => false]);
                $hasil[$status]++;
            }

            return $hasil;
        });
    }

    /**
     * Tentukan kelas tujuan siswa yang naik: override dari admin jika valid,
     * kalau tidak kembali ke target otomatis / kelas asal.
     *
     * @param  Collection<int, Kelas>  $kelasTargets
     */
    private function resolveTargetKelas(
        Kelas $kelas,
        mixed $kelasTargetOverride,
        Collection $kelasTargets,
        ?Kelas $targetOtomatis,
    ): Kelas {
        if ($kelasTargetOverride === null || $kelasTargetOverride === '') {
            return $targetOtomatis ?? $kelas;
        }

        $target = $kelasTargets->get((int) $kelasTargetOverride);

        $tingkat = $kelas->tingkatSekarang();
        $valid = $target !== null
            && $target->children()->doesntExist()
            && $target->tingkatSekarang() === Kelas::tingkatBerikutnya($tingkat);

        if (! $valid) {
            throw ValidationException::withMessages([
                'pilihan' => "Kelas tujuan untuk siswa di {$kelas->nama} tidak valid: kelas harus rombongan belajar aktif tingkat ".Kelas::tingkatBerikutnya($tingkat).'.',
            ]);
        }

        return $target;
    }

    /**
     * Hitung tingkat & kelas tujuan untuk setiap kelas sekali, lalu reuse.
     *
     * @param  Collection<int, int>  $kelasIds
     * @return array<int, array{tingkat: ?string, target: ?Kelas}>
     */
    private function kelasPlan(Collection $kelasIds): array
    {
        if ($kelasIds->isEmpty()) {
            return [];
        }

        $plan = [];

        Kelas::query()
            ->whereIn('id', $kelasIds)
            ->with('parent.parent')
            ->get()
            ->each(function (Kelas $kelas) use (&$plan): void {
                $plan[$kelas->id] = [
                    'tingkat' => $kelas->tingkatSekarang(),
                    'target' => $kelas->promoteTarget(),
                ];
            });

        return $plan;
    }
}
