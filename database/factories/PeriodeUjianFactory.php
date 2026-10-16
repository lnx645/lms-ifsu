<?php

namespace Database\Factories;

use App\Models\PeriodeUjian;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodeUjian>
 */
class PeriodeUjianFactory extends Factory
{
    protected $model = PeriodeUjian::class;

    public function definition(): array
    {
        return [
            'kategori' => fake()->randomElement(['uts', 'uas', 'usbk']),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'nama' => fake()->words(3, true),
            'tanggal_mulai' => now()->subDay(),
            'tanggal_selesai' => now()->addWeek(),
        ];
    }
}
