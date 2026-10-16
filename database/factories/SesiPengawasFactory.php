<?php

namespace Database\Factories;

use App\Models\SesiPengawas;
use App\Models\SesiUjian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SesiPengawas>
 */
class SesiPengawasFactory extends Factory
{
    protected $model = SesiPengawas::class;

    public function definition(): array
    {
        return [
            'sesi_ujian_id' => SesiUjian::factory(),
            'guru_id' => null,
            'nama_pengawas' => $this->faker->name(),
            'peran' => 'pengawas',
        ];
    }
}
