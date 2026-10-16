<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Rekayasa Perangkat Lunak', 'kode' => 'RPL'],
            ['name' => 'Desain Komunikasi Visual', 'kode' => 'DKV'],
            ['name' => 'Kelas Industri', 'kode' => 'IND'],
            ['name' => 'Kelas Axio', 'kode' => 'AXIO'],
        ];

        foreach ($data as $item) {
            Jurusan::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
