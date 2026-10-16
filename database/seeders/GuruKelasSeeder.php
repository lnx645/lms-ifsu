<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Matpel;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class GuruKelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Setiap guru mengampu 1-2 mata pelajaran pada rombel yang ia wali
     * untuk tahun ajaran aktif.
     */
    public function run(): void
    {
        $tahunAjaranId = TahunAjaran::where('active', true)->firstOrFail()->id;
        $matpelIds = Matpel::query()->pluck('id')->all();

        foreach (Guru::with('kelas')->get() as $guru) {
            foreach ($guru->kelas as $kelas) {
                $jumlahMatpel = rand(1, 2);
                $pilihanMatpel = collect($matpelIds)->shuffle()->take($jumlahMatpel);

                foreach ($pilihanMatpel as $matpelId) {
                    GuruKelas::updateOrCreate(
                        [
                            'guru_id' => $guru->id,
                            'kelas_id' => $kelas->id,
                            'matpel_id' => $matpelId,
                            'tahun_ajaran_id' => $tahunAjaranId,
                        ],
                        ['aktif' => true],
                    );
                }
            }
        }
    }
}
