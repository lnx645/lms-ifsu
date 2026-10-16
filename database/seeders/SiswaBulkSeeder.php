<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class SiswaBulkSeeder extends Seeder
{
    /**
     * Jumlah peserta didik yang akan dibuat.
     */
    public int $count = 5000;

    /**
     * Buat akun user untuk setiap peserta didik.
     */
    public bool $withAccounts = true;

    /**
     * Sebar peserta didik ke kelas yang ada.
     */
    public bool $withKelas = true;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelases = Kelas::leaf()->get();
        $tahunAjaranId = TahunAjaran::where('active', true)->value('id')
            ?? TahunAjaran::orderByDesc('id')->value('id');

        if ($kelases->isEmpty()) {
            $this->command?->warn('Tidak ada kelas, siswa dibuat tanpa kelas.');

            $this->withKelas = false;
        }

        $chunk = 100;
        $created = 0;

        while ($created < $this->count) {
            $batch = min($chunk, $this->count - $created);

            $siswas = Siswa::factory()->count($batch)->create();

            foreach ($siswas as $i => $siswa) {
                if ($this->withKelas && ! $kelases->isEmpty()) {
                    SiswaKelas::create([
                        'siswa_nisn' => $siswa->nisn,
                        'kelas_id' => $kelases->get(($created + $i) % $kelases->count())->id,
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'active' => true,
                        'pertama_masuk' => true,
                    ]);
                }

                if ($this->withAccounts) {
                    $siswa->user()->create([
                        'name' => $siswa->nama_lengkap,
                        'email' => $siswa->nisn.'@smkifsu.sch.id',
                        'password' => 'password',
                        'role' => 'siswa',
                    ]);
                }
            }

            $created += $batch;
            $this->command?->info("{$created}/{$this->count} peserta didik dibuat…");
        }
    }
}
