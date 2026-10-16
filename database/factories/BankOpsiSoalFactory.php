<?php

namespace Database\Factories;

use App\Models\BankOpsiSoal;
use App\Models\BankSoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankOpsiSoal>
 */
class BankOpsiSoalFactory extends Factory
{
    protected $model = BankOpsiSoal::class;

    public function definition(): array
    {
        return [
            'bank_soal_id' => BankSoal::factory(),
            'teks' => fake()->words(3, true),
            'benar' => false,
            'urutan' => 0,
        ];
    }

    public function benar(): static
    {
        return $this->state(fn (): array => ['benar' => true]);
    }
}
