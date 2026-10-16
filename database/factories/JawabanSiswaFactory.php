<?php

namespace Database\Factories;

use App\Models\JawabanSiswa;
use App\Models\Soal;
use App\Models\UjianPengerjaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JawabanSiswa>
 */
class JawabanSiswaFactory extends Factory
{
    protected $model = JawabanSiswa::class;

    public function definition(): array
    {
        return [
            'ujian_pengerjaan_id' => UjianPengerjaan::factory(),
            'soal_id' => Soal::factory(),
            'opsi_dipilih' => null,
            'jawaban_teks' => null,
            'benar' => null,
            'skor' => null,
        ];
    }
}
