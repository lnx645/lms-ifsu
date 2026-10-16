<?php

namespace Database\Factories;

use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\Matpel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankSoal>
 */
class BankSoalFactory extends Factory
{
    protected $model = BankSoal::class;

    public function definition(): array
    {
        return [
            'matpel_id' => Matpel::factory(),
            'guru_id' => Guru::factory(),
            'tipe' => 'pg',
            'pertanyaan' => '<p>'.fake()->sentence().'</p>',
            'poin' => 10,
            'topik' => fake()->optional()->word(),
            'kesulitan' => fake()->randomElement(BankSoal::KESULITAN),
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
    public function isian(array $kunci): static
    {
        return $this->state(fn (): array => [
            'tipe' => 'isian',
            'kunci_isian' => $kunci,
        ]);
    }
}
