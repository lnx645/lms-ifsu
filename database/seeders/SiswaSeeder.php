<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Setiap rombongan belajar (kelas leaf) diisi 10 siswa beserta akun
     * login dan penempatan pada tahun ajaran aktif.
     */
    public function run(): void
    {
        $tahunAjaranId = TahunAjaran::where('active', true)->firstOrFail()->id;

        foreach (Kelas::leaf()->orderBy('nama')->get() as $kelas) {
            for ($i = 1; $i <= 10; $i++) {
                $siswa = Siswa::factory()->create();

                SiswaKelas::create([
                    'siswa_nisn' => $siswa->nisn,
                    'kelas_id' => $kelas->id,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'active' => true,
                    'pertama_masuk' => true,
                ]);

                $siswa->user()->create([
                    'name' => $siswa->nama_lengkap,
                    'email' => $siswa->nisn.'@smkifsu.sch.id',
                    'password' => 'password',
                    'role' => 'siswa',
                ]);
            }
        }
    }
}
