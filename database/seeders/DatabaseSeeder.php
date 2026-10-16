<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            JurusanSeeder::class,
            MatpelSeeder::class,
            TahunAjaranSeeder::class,
            GuruSeeder::class,
            KelasSeeder::class,
            SiswaSeeder::class,
            GuruKelasSeeder::class,
            UjianSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@smkifsu.sch.id',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }
}
