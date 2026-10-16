<?php

namespace Database\Seeders;

use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahun = [
            '2022/2023',
            '2023/2024',
            '2024/2025',
            '2025/2026',
        ];

        foreach ($tahun as $i => $nama) {
            TahunAjaran::factory()->create([
                'name' => $nama,
                'active' => $i === count($tahun) - 1,
            ]);
        }
    }
}
