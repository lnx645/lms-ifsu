<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->randomElement(['X', 'XI', 'XII']).' '.$this->faker->randomElement(['A', 'B', 'C']),
            'tingkat' => $this->faker->randomElement(['X', 'XI', 'XII']),
            'deskripsi' => $this->faker->sentence(),
            'guru_id' => null,
            'active' => true,
            'parent_id' => null,
        ];
    }
}
