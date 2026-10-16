<?php

namespace Database\Factories;

use App\Models\Penilaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penilaian>
 */
class PenilaianFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Penilaian::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->sentence(3),
            'deskripsi' => fake()->optional()->paragraph(),
            'tipe' => fake()->randomElement(['kognitif', 'sikap', 'tugas', 'cbt']),
            'nilai_maks' => fake()->numberBetween(100, 1000),
            'bobot' => fake()->numberBetween(1, 100),
            'aktif' => fake()->boolean(),
            'sumber' => 'manual',
        ];
    }
}
