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

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);

    $this->guru = Guru::factory()->create();
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
    $this->siswaAUser = User::factory()->create(['nisn' => $this->siswaA->nisn]);

    $this->siswaB = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswaB->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
});

test('siswa dapat membuka halaman nilai', function (): void {
    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Penilaian/Index')
            ->has('matpel', 1)
            ->where('matpel.0.matpel', 'Matematika'));
});

test('halaman nilai menampilkan nilai tugas dan penilaian manual', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'judul' => 'Latihan Integral',
        'poin' => 100,
    ]);
    TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'submitted_at' => now(),
        'nilai' => 85,
    ]);

    $penilaian = Penilaian::factory()->create([
        'nama' => 'PTS Ganjil',
        'tipe' => 'kognitif',
        'nilai_maks' => 100,
        'aktif' => true,
    ]);
    DetailPenilaian::create([
        'penilaian_id' => $penilaian->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 92,
        'sumber' => 'manual',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('matpel.0.nilai', 2)
            ->where('matpel.0.nilai.0.nama', 'PTS Ganjil')
            ->where('matpel.0.nilai.0.nilai', 92)
            ->where('matpel.0.nilai.0.sumber', 'manual')
            ->where('matpel.0.nilai.1.nama', 'Latihan Integral')
            ->where('matpel.0.nilai.1.nilai', 85)
            ->where('matpel.0.nilai.1.sumber', 'tugas')
            ->where('matpel.0.nilai.1.nilai_maks', 100));
});

test('halaman nilai hanya menampilkan nilai milik siswa sendiri', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'judul' => 'Tugas Rahasia',
        'poin' => 100,
    ]);
    TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaB->nisn,
        'submitted_at' => now(),
        'nilai' => 100,
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('matpel.0.nilai', 1)
            ->where('matpel.0.nilai.0.nilai', null));
});

test('halaman nilai tidak menampilkan penilaian nonaktif', function (): void {
    $penilaian = Penilaian::factory()->create([
        'nama' => 'PTS Lama',
        'nilai_maks' => 100,
        'aktif' => false,
    ]);
    DetailPenilaian::create([
        'penilaian_id' => $penilaian->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 60,
        'sumber' => 'manual',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('matpel.0.nilai', []));
});

test('siswa tanpa penugasan melihat daftar matpel kosong', function (): void {
    $kelasLain = Kelas::factory()->create(['nama' => 'XI-RPL-2']);
    $siswaC = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $siswaC->nisn,
        'kelas_id' => $kelasLain->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $siswaCUser = User::factory()->create(['nisn' => $siswaC->nisn]);

    $this->actingAs($siswaCUser)
        ->get(route('app.siswa.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('matpel', []));
});
