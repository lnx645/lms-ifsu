<?php

namespace Database\Factories;

use App\Models\PelanggaranLog;
use App\Models\UjianPengerjaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PelanggaranLog>
 */
class PelanggaranLogFactory extends Factory
{
    protected $model = PelanggaranLog::class;

    public function definition(): array
    {
        return [
            'ujian_pengerjaan_id' => UjianPengerjaan::factory(),
            'jenis' => fake()->randomElement(PelanggaranLog::JENIS),
            'terjadi_at' => now(),
        ];
    }
}
