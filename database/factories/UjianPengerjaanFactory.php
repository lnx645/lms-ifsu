<?php

namespace Database\Factories;

use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UjianPengerjaan>
 */
class UjianPengerjaanFactory extends Factory
{
    protected $model = UjianPengerjaan::class;

    public function definition(): array
    {
        return [
            'ujian_id' => Ujian::factory(),
            'siswa_nisn' => fn () => Siswa::factory()->create()->nisn,
            'attempt_ke' => 1,
            'mulai_at' => now()->subMinutes(10),
            'batas_at' => now()->addMinutes(50),
            'submitted_at' => null,
            'status' => 'berlangsung',
            'nilai_objektif' => null,
            'nilai_esai' => null,
            'nilai_total' => null,
            'jumlah_pelanggaran' => 0,
        ];
    }

    public function selesai(): static
    {
        return $this->state(fn (): array => [
            'status' => 'selesai',
            'submitted_at' => now(),
        ]);
    }
}
