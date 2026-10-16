<?php

namespace Database\Factories;

use App\Models\Jurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jurusan>
 */
class JurusanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $list = [
            ['name' => 'Rekayasa Perangkat Lunak', 'kode' => 'RPL'],
            ['name' => 'Teknik Komputer & Jaringan', 'kode' => 'TKJ'],
            ['name' => 'Teknik Kendaraan Ringan', 'kode' => 'TKR'],
            ['name' => 'Bisnis Daring & Pemasaran', 'kode' => 'BDP'],
            ['name' => 'Akuntansi', 'kode' => 'AKT'],
        ];

        return fake()->randomElement($list);
    }
}
