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
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);

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

    $this->siswa = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswa->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);

    $this->penilaian = Penilaian::factory()->create([
        'nama' => 'PTS Ganjil',
        'tipe' => 'kognitif',
        'nilai_maks' => 100,
        'bobot' => 30,
        'aktif' => true,
    ]);
});

test('halaman filter menampilkan daftar penugasan', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.penilaian.penugasan.filter', $this->penilaian))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/DetailPenilaian/Filter')
            ->has('penugasan', 1)
            ->where('penugasan.0.value', $this->guruKelas->id)
            ->where('siswas', null));
});

test('halaman filter menampilkan siswa penugasan yang dipilih', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.penilaian.penugasan.filter', [
            'penilaian' => $this->penilaian,
            'guru_kelas_id' => $this->guruKelas->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('siswas', 1)
            ->where('siswas.0.nisn', $this->siswa->nisn)
            ->where('siswas.0.nilai', null));
});

test('admin dapat menyimpan nilai siswa pada penugasan', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.penilaian.penugasan.storeNilai', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
            'siswa' => $this->siswa,
        ]), [
            'nilai' => 85,
            'keterangan' => 'Diinput admin',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $detail = DetailPenilaian::query()
        ->where('penilaian_id', $this->penilaian->id)
        ->where('guru_kelas_id', $this->guruKelas->id)
        ->where('siswa_nisn', $this->siswa->nisn)
        ->first();

    expect($detail)->not->toBeNull()
        ->and($detail->nilai)->toBe(85.0)
        ->and($detail->sumber)->toBe('manual')
        ->and($detail->tahun_ajaran_id)->toBe($this->tahunAjaran->id)
        ->and($detail->guru_id)->toBeNull();
});

test('admin tidak dapat menyimpan nilai melebihi nilai maksimum', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.penilaian.penugasan.storeNilai', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
            'siswa' => $this->siswa,
        ]), [
            'nilai' => 500,
        ])
        ->assertSessionHasErrors('nilai');

    expect(DetailPenilaian::count())->toBe(0);
});

test('admin tidak dapat menilai siswa di luar penugasan', function (): void {
    $kelasLain = Kelas::factory()->create(['nama' => 'XI-RPL-2']);
    $siswaLain = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $siswaLain->nisn,
        'kelas_id' => $kelasLain->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.penilaian.penugasan.storeNilai', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
            'siswa' => $siswaLain,
        ]), [
            'nilai' => 80,
        ])
        ->assertStatus(422);

    expect(DetailPenilaian::count())->toBe(0);
});

test('halaman detail nilai menampilkan nilai yang sudah ada', function (): void {
    DetailPenilaian::create([
        'penilaian_id' => $this->penilaian->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswa->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 77,
        'sumber' => 'tugas',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.penilaian.penugasan.detail', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
            'siswa' => $this->siswa,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/DetailPenilaian/Show')
            ->where('detail.nilai', 77)
            ->where('detail.sumber', 'tugas'));
});
