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
        'sumber' => 'manual',
    ]);
});

test('guru dapat membuka halaman penilaian', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Penilaian/Index')
            ->has('penilaian', 1)
            ->where('penilaian.0.nama', 'PTS Ganjil')
            ->where('penilaian.0.sumber', 'manual')
            ->has('penugasan', 1)
            ->where('penugasan.0.value', $this->guruKelas->id));
});

test('halaman penilaian hanya menampilkan penilaian aktif', function (): void {
    Penilaian::factory()->create([
        'nama' => 'Tersembunyi',
        'aktif' => false,
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('penilaian', 1));
});

test('guru dapat membuka tabel nilai untuk penugasan sendiri', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.penilaian.show', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Penilaian/Input')
            ->has('siswas', 1)
            ->where('siswas.0.nisn', $this->siswa->nisn)
            ->where('siswas.0.nilai', null));
});

test('guru tidak dapat membuka penugasan milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.penilaian.show', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $guruKelasLain,
        ]))
        ->assertNotFound();
});

test('guru dapat menyimpan nilai manual', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.penilaian.store', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
        ]), [
            'siswa_nisn' => $this->siswa->nisn,
            'nilai' => 90,
            'keterangan' => 'Bagus sekali',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $detail = DetailPenilaian::query()
        ->where('penilaian_id', $this->penilaian->id)
        ->where('guru_kelas_id', $this->guruKelas->id)
        ->where('siswa_nisn', $this->siswa->nisn)
        ->first();

    expect($detail)->not->toBeNull()
        ->and($detail->nilai)->toBe(90.0)
        ->and($detail->sumber)->toBe('manual')
        ->and($detail->keterangan)->toBe('Bagus sekali')
        ->and($detail->guru_id)->toBe($this->guru->id)
        ->and($detail->tahun_ajaran_id)->toBe($this->tahunAjaran->id);
});

test('guru dapat memperbarui nilai manual yang sudah ada', function (): void {
    DetailPenilaian::create([
        'penilaian_id' => $this->penilaian->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswa->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 70,
        'sumber' => 'manual',
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.penilaian.store', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
        ]), [
            'siswa_nisn' => $this->siswa->nisn,
            'nilai' => 95,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(DetailPenilaian::query()->first()->nilai)->toBe(95.0);
});

test('guru tidak dapat menyimpan nilai melebihi nilai maksimum', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.penilaian.store', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
        ]), [
            'siswa_nisn' => $this->siswa->nisn,
            'nilai' => 200,
        ])
        ->assertSessionHasErrors('nilai');

    expect(DetailPenilaian::count())->toBe(0);
});

test('penilaian nonaktif tidak dapat diinput guru', function (): void {
    $this->penilaian->update(['aktif' => false]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.penilaian.store', [
            'penilaian' => $this->penilaian,
            'guruKelas' => $this->guruKelas,
        ]), [
            'siswa_nisn' => $this->siswa->nisn,
            'nilai' => 80,
        ])
        ->assertNotFound();
});
