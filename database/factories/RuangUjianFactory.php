<?php

namespace Database\Factories;

use App\Models\RuangUjian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RuangUjian>
 */
class RuangUjianFactory extends Factory
{
    protected $model = RuangUjian::class;

    public function definition(): array
    {
        return [
            'kode' => 'R'.fake()->unique()->numberBetween(100, 999),
            'nama' => 'Ruang '.fake()->numberBetween(1, 20),
            'kapasitas' => 40,
            'lokasi' => 'Gedung A',
        ];
    }
}
