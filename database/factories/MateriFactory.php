<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Materi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materi>
 */
class MateriFactory extends Factory
{
    protected $model = Materi::class;

    public function definition(): array
    {
        return [
            'guru_id' => Guru::factory(),
            'guru_kelas_id' => GuruKelas::factory(),
            'judul' => $this->faker->sentence(4),
            'deskripsi' => $this->faker->paragraph,
            'file_path' => 'materi/'.$this->faker->uuid().'.pdf',
            'file_name' => $this->faker->word().'.pdf',
            'file_size' => $this->faker->numberBetween(1024, 5_242_880),
            'mime_type' => 'application/pdf',
        ];
    }
}
