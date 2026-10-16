<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    /**
     * Struktur rombongan belajar per jurusan per tingkat.
     *
     * @var array<string, array<string, int>>
     */
    public const STRUKTUR = [
        'RPL' => ['X' => 10, 'XI' => 11, 'XII' => 12],
        'DKV' => ['X' => 5, 'XI' => 5, 'XII' => 5],
        'IND' => ['X' => 2, 'XI' => 2, 'XII' => 2],
        'AXIO' => ['X' => 3, 'XI' => 3, 'XII' => 3],
    ];

    /**
     * Jumlah seluruh rombongan belajar (dipakai GuruSeeder).
     */
    public static function totalRombel(): int
    {
        return collect(self::STRUKTUR)->flatten()->sum();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guruIds = Guru::query()->pluck('id')->shuffle()->all();
        $guruCursor = 0;

        foreach (['X', 'XI', 'XII'] as $tingkat) {
            $root = Kelas::factory()->create([
                'nama' => $tingkat,
                'tingkat' => $tingkat,
                'guru_id' => null,
                'jurusan_id' => null,
                'parent_id' => null,
                'active' => true,
            ]);

            foreach (self::STRUKTUR as $kode => $perTingkat) {
                $jurusan = Jurusan::where('kode', $kode)->firstOrFail();

                $parent = Kelas::factory()->create([
                    'nama' => $tingkat.'-'.$kode,
                    'guru_id' => null,
                    'jurusan_id' => $jurusan->id,
                    'parent_id' => $root->id,
                    'active' => true,
                ]);

                for ($nomor = 1; $nomor <= $perTingkat[$tingkat]; $nomor++) {
                    Kelas::factory()->create([
                        'nama' => $parent->nama.'-'.$nomor,
                        'guru_id' => $guruIds[$guruCursor++],
                        'jurusan_id' => $jurusan->id,
                        'parent_id' => $parent->id,
                        'active' => true,
                    ]);
                }
            }
        }
    }
}
