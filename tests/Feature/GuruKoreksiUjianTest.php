<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Models\User;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'IPS']);
    $this->kelas = Kelas::factory()->create(['nama' => 'XI-RPL-1']);

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

    $this->penilaian = Penilaian::create([
        'nama' => 'UTS', 'tipe' => 'cbt', 'nilai_maks' => 100, 'bobot' => 2, 'aktif' => true, 'sumber' => 'uts',
    ]);
    $this->ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'penilaian_id' => $this->penilaian->id,
        'kategori' => 'uts',
        'status' => 'terbit',
        'nilai_maks' => 100,
    ]);
    $this->soalEsai = Soal::factory()->for($this->ujian)->esai()->create(['poin' => 100]);

    $this->pengerjaan = UjianPengerjaan::create([
        'ujian_id' => $this->ujian->id,
        'siswa_nisn' => $this->siswa->nisn,
        'attempt_ke' => 1,
        'mulai_at' => now()->subHour(),
        'batas_at' => now()->subMinutes(30),
        'submitted_at' => now()->subMinutes(30),
        'status' => 'selesai',
    ]);
    $this->jawaban = JawabanSiswa::create([
        'ujian_pengerjaan_id' => $this->pengerjaan->id,
        'soal_id' => $this->soalEsai->id,
        'jawaban_teks' => 'Jawaban esai siswa',
    ]);
});

test('guru dapat membuka rekap hasil ujian', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.ujian.hasil.index', $this->ujian))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Ujian/Hasil')
            ->where('ujian.ada_esai', true)
            ->has('siswas', 1));
});

test('guru dapat membuka detail koreksi', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.ujian.hasil.show', [$this->ujian, $this->pengerjaan]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Ujian/Koreksi')
            ->has('jawabans', 1));
});

test('guru menilai esai dan nilai final tersinkron ke penilaian', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.hasil.nilai', [$this->ujian, $this->pengerjaan, $this->jawaban]), [
            'skor' => 80,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->jawaban->refresh();
    expect((float) $this->jawaban->skor)->toBe(80.0);

    $this->pengerjaan->refresh();
    expect($this->pengerjaan->nilai_total)->toBe(80.0);

    $this->assertDatabaseHas('detail_penilaian', [
        'penilaian_id' => $this->penilaian->id,
        'siswa_nisn' => $this->siswa->nisn,
        'nilai' => 80,
        'sumber' => 'uts',
    ]);
});

test('skor esai tidak boleh melebihi poin soal', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.hasil.nilai', [$this->ujian, $this->pengerjaan, $this->jawaban]), [
            'skor' => 150,
        ])
        ->assertSessionHasErrors('skor');
});

test('guru lain tidak dapat mengoreksi', function (): void {
    $guruLain = Guru::factory()->create();
    $guruLainUser = User::factory()->create(['guru_id' => $guruLain->id]);

    $this->actingAs($guruLainUser)
        ->get(route('app.guru.ujian.hasil.show', [$this->ujian, $this->pengerjaan]))
        ->assertNotFound();
});
