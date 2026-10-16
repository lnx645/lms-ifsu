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
            'nama' => $this->faker->sentence(3),
            'deskripsi' => $this->faker->optional()->paragraph(),
            'tipe' => $this->faker->randomElement(['kognitif', 'sikap', 'tugas', 'cbt']),
            'nilai_maks' => $this->faker->numberBetween(100, 1000),
            'bobot' => $this->faker->numberBetween(1, 100),
            'aktif' => $this->faker->boolean(),
            'sumber' => 'manual',
        ];
    }
}
