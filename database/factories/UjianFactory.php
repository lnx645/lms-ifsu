<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Ujian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ujian>
 */
class UjianFactory extends Factory
{
    protected $model = Ujian::class;

    public function definition(): array
    {
        return [
            'guru_id' => Guru::factory(),
            'guru_kelas_id' => GuruKelas::factory(),
            'penilaian_id' => null,
            'dibuat_oleh_admin' => false,
            'kategori' => 'kuis',
            'judul' => fake()->sentence(4),
            'deskripsi' => fake()->optional()->paragraph(),
            'tanggal_mulai' => now()->subHour(),
            'tanggal_selesai' => now()->addWeek(),
            'durasi_menit' => 60,
            'maks_attempt' => 1,
            'acak_soal' => false,
            'acak_opsi' => false,
            'tampilkan_hasil' => true,
            'wajib_fullscreen' => false,
            'maks_pelanggaran' => 0,
            'token' => null,
            'token_released_at' => null,
            'nilai_maks' => 100,
            'bobot' => 1,
            'status' => 'draft',
        ];
    }

    public function terbit(): static
    {
        return $this->state(fn (): array => ['status' => 'terbit']);
    }

    public function kategori(string $kategori): static
    {
        return $this->state(fn (): array => ['kategori' => $kategori]);
    }
}
