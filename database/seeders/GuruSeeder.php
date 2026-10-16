<?php

namespace Database\Seeders;

use App\Models\Guru;
use Illuminate\Database\Seeder;

class GuruSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Setiap rombongan belajar punya satu wali kelas (guru). Jumlah guru
     * mengikuti jumlah rombel pada KelasSeeder.
     */
    public function run(): void
    {
        $gurus = Guru::factory()->count(KelasSeeder::totalRombel())->create();

        foreach ($gurus as $index => $guru) {
            $guru->user()->create([
                'name' => $guru->nama_lengkap,
                'email' => sprintf('guru.%03d@smkifsu.sch.id', $index + 1),
                'password' => 'password',
                'role' => 'guru',
            ]);
        }
    }
}
