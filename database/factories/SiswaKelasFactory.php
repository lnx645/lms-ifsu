<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiswaKelas>
 */
class SiswaKelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_nisn' => Siswa::factory(),
            'kelas_id' => Kelas::factory(),
            'tahun_ajaran_id' => TahunAjaran::where('active', true)->first()?->id ?? TahunAjaran::factory(),
            'active' => true,
            'pertama_masuk' => false,
        ];
    }
}
