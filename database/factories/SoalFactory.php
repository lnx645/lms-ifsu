<?php

namespace Database\Factories;

use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Soal>
 */
class SoalFactory extends Factory
{
    protected $model = Soal::class;

    public function definition(): array
    {
        return [
            'ujian_id' => Ujian::factory(),
            'tipe' => 'pg',
            'pertanyaan' => '<p>'.fake()->sentence().'</p>',
            'poin' => 1,
            'urutan' => 0,
            'kunci_isian' => null,
            'isian_case_sensitive' => false,
        ];
    }

    public function tipe(string $tipe): static
    {
        return $this->state(fn (): array => ['tipe' => $tipe]);
    }

    public function esai(): static
    {
        return $this->state(fn (): array => ['tipe' => 'esai']);
    }

    /**
     * @param  array<int, string>  $kunci
     */
    public function isian(array $kunci, bool $caseSensitive = false): static
    {
        return $this->state(fn (): array => [
            'tipe' => 'isian',
            'kunci_isian' => $kunci,
            'isian_case_sensitive' => $caseSensitive,
        ]);
    }
}
