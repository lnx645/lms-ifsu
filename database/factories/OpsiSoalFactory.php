<?php

namespace Database\Factories;

use App\Models\OpsiSoal;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpsiSoal>
 */
class OpsiSoalFactory extends Factory
{
    protected $model = OpsiSoal::class;

    public function definition(): array
    {
        return [
            'soal_id' => Soal::factory(),
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
