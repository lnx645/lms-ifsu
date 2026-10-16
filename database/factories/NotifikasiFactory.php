<?php

namespace Database\Factories;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notifikasi>
 */
class NotifikasiFactory extends Factory
{
    protected $model = Notifikasi::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tipe' => 'info',
            'judul' => fake()->sentence(4),
            'pesan' => fake()->sentence(10),
            'tautan' => null,
            'dibaca_at' => null,
        ];
    }
}
