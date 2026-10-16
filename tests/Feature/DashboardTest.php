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
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->kelasA = Kelas::factory()->create(['nama' => 'X-RPL-1']);
    $this->kelasB = Kelas::factory()->create(['nama' => 'X-RPL-2']);

    $this->guru = Guru::factory()->create();
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->guruKelasA = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->siswaA = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswaA->nisn,
        'kelas_id' => $this->kelasA->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $this->siswaAUser = User::factory()->create(['nisn' => $this->siswaA->nisn]);
});

test('dashboard siswa menampilkan sambutan, kutipan, ringkasan matpel, dan materi terbaru', function (): void {
    Materi::factory()->count(6)->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Bab 1: Bilangan',
    ]);
    $terbaru = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Materi Terbaru',
        'created_at' => now()->addMinute(),
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('nama', $this->siswaA->nama_lengkap)
            ->where('kelas', 'X-RPL-1')
            ->where('tahunAjaran', $this->tahunAjaran->name)
            ->has('kutipan.teks')
            ->has('ringkasan', 1)
            ->where('ringkasan.0.matpel', 'Matematika')
            ->where('ringkasan.0.total', 7)
            ->has('materiTerbaru', 5)
            ->where('materiTerbaru.0.id', $terbaru->id));
});

test('dashboard siswa hanya menampilkan materi dari kelasnya sendiri', function (): void {
    $guruLain = Guru::factory()->create();
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    Materi::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $guruKelasLain->id,
        'judul' => 'Materi Kelas Lain',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('materiTerbaru', 0)
            ->has('ringkasan', 0));
});

test('dashboard guru menampilkan nama pengguna', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('nama', $this->guru->nama_lengkap));
});
