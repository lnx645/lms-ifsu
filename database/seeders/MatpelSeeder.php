<?php

namespace Database\Seeders;

use App\Models\Matpel;
use Illuminate\Database\Seeder;

class MatpelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matpels = [
            ['name' => 'Dasar Pemrograman', 'description' => 'Konsep dasar logika dan pemrograman komputer.'],
            ['name' => 'Pemrograman Web', 'description' => 'Pembuatan aplikasi berbasis web dengan HTML, CSS, dan JavaScript.'],
            ['name' => 'Pemrograman Berbasis Objek', 'description' => 'Paradigma OOP menggunakan bahasa tingkat tinggi.'],
            ['name' => 'Basis Data', 'description' => 'Perancangan dan pengelolaan basis data relasional.'],
            ['name' => 'Algoritma dan Struktur Data', 'description' => 'Studi algoritma dan struktur data fundamental.'],
            ['name' => 'Jaringan Komputer', 'description' => 'Konsep dan konfigurasi jaringan komputer dasar.'],
            ['name' => 'Sistem Operasi', 'description' => 'Manajemen sistem operasi dan layanannya.'],
            ['name' => 'Keamanan Jaringan', 'description' => 'Penerapan keamanan pada sistem dan jaringan.'],
            ['name' => 'Pengembangan Aplikasi Mobile', 'description' => 'Pembuatan aplikasi perangkat bergerak.'],
            ['name' => 'Komputer dan Jaringan Dasar', 'description' => 'Pengenalan perangkat keras dan jaringan komputer.'],
        ];

        foreach ($matpels as $matpel) {
            Matpel::firstOrCreate(
                ['name' => $matpel['name']],
                ['description' => $matpel['description']]
            );
        }
    }
}
