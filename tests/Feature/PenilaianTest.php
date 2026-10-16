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

test('admin dapat membuka halaman penilaian', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Penilaian/Index')
            ->has('penilaian', 1)
            ->where('penilaian.0.nama', 'PTS Ganjil')
            ->where('penilaian.0.sumber', 'manual'));
});

test('admin dapat menambah penilaian dan diarahkan ke daftar', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.penilaian.store'), [
            'nama' => 'UH Bab 3',
            'deskripsi' => 'Ulangan harian',
            'tipe' => 'kognitif',
            'nilai_maks' => 100,
            'bobot' => 20,
            'aktif' => true,
        ])
        ->assertRedirect(route('admin.penilaian.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('penilaian', [
        'nama' => 'UH Bab 3',
        'sumber' => 'manual',
    ]);
});

test('admin tidak dapat mengirim sumber penilaian', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.penilaian.store'), [
            'nama' => 'Tugas Palsu',
            'tipe' => 'tugas',
            'nilai_maks' => 100,
            'bobot' => 10,
            'aktif' => true,
            'sumber' => 'tugas',
        ])
        ->assertSessionHasErrors('sumber');

    $this->assertDatabaseMissing('penilaian', ['nama' => 'Tugas Palsu']);
});

test('admin dapat memperbarui penilaian', function (): void {
    $this->actingAs($this->admin)
        ->put(route('admin.penilaian.update', $this->penilaian), [
            'nama' => 'PTS Ganjil Revisi',
            'tipe' => 'kognitif',
            'nilai_maks' => 120,
            'bobot' => 25,
            'aktif' => false,
        ])
        ->assertRedirect(route('admin.penilaian.index'))
        ->assertSessionHasNoErrors();

    expect($this->penilaian->fresh())
        ->nama->toBe('PTS Ganjil Revisi')
        ->nilai_maks->toBe(120.0)
        ->aktif->toBeFalse();
});

test('admin tidak dapat menghapus penilaian yang sudah punya nilai', function (): void {
    DetailPenilaian::create([
        'penilaian_id' => $this->penilaian->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswa->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 80,
        'sumber' => 'manual',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.penilaian.destroy', $this->penilaian))
        ->assertRedirect()
        ->assertSessionHas('inertia');

    expect(Penilaian::find($this->penilaian->id))->not->toBeNull();
});

test('admin dapat menghapus penilaian tanpa nilai', function (): void {
    $this->actingAs($this->admin)
        ->delete(route('admin.penilaian.destroy', $this->penilaian))
        ->assertRedirect(route('admin.penilaian.index'));

    expect(Penilaian::find($this->penilaian->id))->toBeNull();
});
