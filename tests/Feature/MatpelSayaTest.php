<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Matpel;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matematika = Matpel::factory()->create(['name' => 'Matematika']);
    $this->informatika = Matpel::factory()->create(['name' => 'Informatika']);
    $this->kelasA = Kelas::factory()->create(['nama' => 'X-RPL-1']);
    $this->kelasB = Kelas::factory()->create(['nama' => 'X-RPL-2']);

    $this->guru = Guru::factory()->create();
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->siswaA = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswaA->nisn,
        'kelas_id' => $this->kelasA->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $this->siswaAUser = User::factory()->create(['nisn' => $this->siswaA->nisn]);
});

test('halaman matpel-saya guru menampilkan matpel yang diajar beserta kelas dan jumlah materi', function (): void {
    $gkA = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->matematika->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    $gkB = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->matematika->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->informatika->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    Materi::factory()->create(['guru_id' => $this->guru->id, 'guru_kelas_id' => $gkA->id]);
    Materi::factory()->count(2)->create(['guru_id' => $this->guru->id, 'guru_kelas_id' => $gkB->id]);

    $this->actingAs($this->guruUser)
        ->get(route('app.matpel'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Matpel')
            ->where('role', 'guru')
            ->where('tahunAjaran', $this->tahunAjaran->name)
            ->has('matpels', 2)
            ->where('matpels.0.name', 'Matematika')
            ->has('matpels.0.kelas', 2)
            ->where('matpels.0.total_materi', 3)
            ->where('matpels.1.name', 'Informatika')
            ->where('matpels.1.kelas.0.nama', 'X-RPL-1')
            ->where('matpels.1.total_materi', 0));
});

test('halaman matpel-saya guru menampilkan matpel yang diajar di tahun ajaran lain', function (): void {
    $tahunLain = TahunAjaran::factory()->create(['active' => false, 'name' => '2024/2025']);
    GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->matematika->id,
        'tahun_ajaran_id' => $tahunLain->id,
        'aktif' => true,
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.matpel'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Matpel')
            ->has('matpels', 0));
});

test('halaman matpel-saya siswa menampilkan matpel kelasnya beserta guru pengampu', function (): void {
    GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->matematika->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    $guruLain = Guru::factory()->create();
    GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->informatika->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.matpel'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Matpel')
            ->where('role', 'siswa')
            ->where('kelas', 'X-RPL-1')
            ->has('matpels', 1)
            ->where('matpels.0.name', 'Matematika')
            ->where('matpels.0.guru', $this->guru->nama_lengkap));
});
