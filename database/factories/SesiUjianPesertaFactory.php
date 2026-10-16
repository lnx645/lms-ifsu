<?php

namespace Database\Factories;

use App\Models\SesiUjian;
use App\Models\SesiUjianPeserta;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SesiUjianPeserta>
 */
class SesiUjianPesertaFactory extends Factory
{
    protected $model = SesiUjianPeserta::class;

    public function definition(): array
    {
        $sesi = SesiUjian::factory()->create();

        return [
            'sesi_ujian_id' => $sesi->id,
            'ujian_id' => $sesi->ujian_id,
            'siswa_nisn' => Siswa::factory(),
        ];
    }
}
