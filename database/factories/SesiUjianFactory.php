<?php

namespace Database\Factories;

use App\Models\SesiUjian;
use App\Models\Ujian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SesiUjian>
 */
class SesiUjianFactory extends Factory
{
    protected $model = SesiUjian::class;

    public function definition(): array
    {
        return [
            'ujian_id' => Ujian::factory(),
            'nama' => 'Sesi 1',
            'urutan' => 1,
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->addHour(),
            'kapasitas' => null,
        ];
    }
}
