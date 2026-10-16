<?php

use App\Models\DetailPenilaian;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\TugasPengumpulan;
use App\Models\User;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);

    $this->guru = Guru::factory()->create();
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->siswaA = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswaA->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);

    $this->siswaB = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswaB->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);

    $this->penilaianPTS = Penilaian::factory()->create([
        'nama' => 'PTS Ganjil',
        'tipe' => 'kognitif',
        'nilai_maks' => 100,
        'aktif' => true,
    ]);

    $this->tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'judul' => 'Latihan Integral',
        'poin' => 100,
    ]);
    $this->penilaianTugas = Penilaian::factory()->create([
        'nama' => 'Tugas: Latihan Integral',
        'tipe' => 'tugas',
        'nilai_maks' => 100,
        'sumber' => 'tugas',
    ]);
    $this->tugas->update(['penilaian_id' => $this->penilaianTugas->id]);

    DetailPenilaian::create([
        'penilaian_id' => $this->penilaianPTS->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 90,
        'sumber' => 'manual',
    ]);
    DetailPenilaian::create([
        'penilaian_id' => $this->penilaianPTS->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswaB->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 80,
        'sumber' => 'manual',
    ]);
    TugasPengumpulan::create([
        'tugas_id' => $this->tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'submitted_at' => now(),
        'nilai' => 85,
    ]);
    DetailPenilaian::create([
        'penilaian_id' => $this->penilaianTugas->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 85,
        'sumber' => 'tugas',
    ]);
});

test('guru dapat membuka halaman rekap nilai', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.penilaian.rekap'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Penilaian/Rekap')
            ->has('penugasan', 1)
            ->where('siswas', null));
});

test('rekap menampilkan kolom penilaian dan nilai siswa', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.penilaian.rekap', [
            'guru_kelas_id' => $this->guruKelas->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('kolom', 2)
            ->where('kolom.0.nama', 'PTS Ganjil')
            ->where('kolom.1.nama', 'Tugas: Latihan Integral')
            ->has('siswas', 2)
            ->where('siswas', function (Collection $siswas): bool {
                $a = $siswas->firstWhere('nisn', $this->siswaA->nisn);
                $b = $siswas->firstWhere('nisn', $this->siswaB->nisn);

                expect($a['nilai'])->toBe([90, 85]);
                expect($a['rata_rata'])->toBe(87.5);
                expect($b['nilai'])->toBe([80, null]);
                expect($b['rata_rata'])->toBe(80);

                return true;
            }));
});

test('rekap hanya menampilkan nilai penugasan milik guru sendiri', function (): void {
    $guruLain = Guru::factory()->create();
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.penilaian.rekap', [
            'guru_kelas_id' => $guruKelasLain->id,
        ]))
        ->assertNotFound();
});
