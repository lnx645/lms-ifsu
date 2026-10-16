<?php

namespace Database\Factories;

use App\Models\BeritaAcaraUjian;
use App\Models\Ujian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeritaAcaraUjian>
 */
class BeritaAcaraUjianFactory extends Factory
{
    protected $model = BeritaAcaraUjian::class;

    public function definition(): array
    {
        return [
            'ujian_id' => Ujian::factory(),
            'sesi_ujian_id' => null,
            'tanggal' => now()->toDateString(),
            'jumlah_peserta' => 30,
            'jumlah_hadir' => 28,
            'jumlah_tidak_hadir' => 2,
            'jumlah_pelanggaran' => 0,
            'catatan' => null,
            'nama_pengawas' => fake()->name(),
            'nama_kepala_sekolah' => null,
        ];
    }
}
