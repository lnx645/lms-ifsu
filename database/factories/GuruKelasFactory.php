<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuruKelas>
 */
class GuruKelasFactory extends Factory
{
    protected $model = GuruKelas::class;

    public function definition(): array
    {
        return [
            'guru_id' => Guru::factory(),
            'kelas_id' => Kelas::factory(),
            'matpel_id' => Matpel::factory(),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'aktif' => $this->faker->boolean(80),
        ];
    }
}
