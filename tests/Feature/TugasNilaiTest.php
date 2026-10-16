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
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');

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
    $this->siswaUser = User::factory()->create(['nisn' => $this->siswa->nisn]);

    $this->tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'poin' => 100,
    ]);
});

test('membuat tugas otomatis membuat penilaian sumber tugas', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.tugas.store'), [
            'guru_kelas_id' => $this->guruKelas->id,
            'judul' => 'Latihan Integral',
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
            'poin' => 50,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $tugas = Tugas::query()->where('judul', 'Latihan Integral')->first();

    expect($tugas)->not->toBeNull()
        ->and($tugas->poin)->toBe(50)
        ->and($tugas->penilaian_id)->not->toBeNull();

    $penilaian = Penilaian::find($tugas->penilaian_id);

    expect($penilaian)->not->toBeNull()
        ->and($penilaian->nama)->toBe('Tugas: Latihan Integral')
        ->and($penilaian->tipe)->toBe('tugas')
        ->and($penilaian->nilai_maks)->toBe(50.0)
        ->and($penilaian->sumber)->toBe('tugas')
        ->and($penilaian->aktif)->toBeTrue();
});

test('guru dapat menilai siswa yang sudah mengumpulkan', function (): void {
    $pengumpulan = TugasPengumpulan::create([
        'tugas_id' => $this->tugas->id,
        'siswa_nisn' => $this->siswa->nisn,
        'jawaban_teks' => 'Jawaban saya',
        'submitted_at' => now(),
    ]);

    $penilaian = Penilaian::factory()->create([
        'nama' => 'Tugas: '.$this->tugas->judul,
        'tipe' => 'tugas',
        'nilai_maks' => $this->tugas->poin,
        'sumber' => 'tugas',
    ]);
    $this->tugas->update(['penilaian_id' => $penilaian->id]);

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.tugas.nilai', $this->tugas), [
            'siswa_nisn' => $this->siswa->nisn,
            'nilai' => 85,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($pengumpulan->fresh()->nilai)->toBe(85.0);

    $detail = DetailPenilaian::query()
        ->where('penilaian_id', $penilaian->id)
        ->where('guru_kelas_id', $this->guruKelas->id)
        ->where('siswa_nisn', $this->siswa->nisn)
        ->first();

    expect($detail)->not->toBeNull()
        ->and($detail->nilai)->toBe(85.0)
        ->and($detail->sumber)->toBe('tugas')
        ->and($detail->guru_id)->toBe($this->guru->id)
        ->and($detail->tahun_ajaran_id)->toBe($this->tahunAjaran->id);
});

test('guru tidak dapat menilai melebihi poin tugas', function (): void {
    TugasPengumpulan::create([
        'tugas_id' => $this->tugas->id,
        'siswa_nisn' => $this->siswa->nisn,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.tugas.nilai', $this->tugas), [
            'siswa_nisn' => $this->siswa->nisn,
            'nilai' => 150,
        ])
        ->assertSessionHasErrors('nilai');

    expect(TugasPengumpulan::query()->first()->nilai)->toBeNull();
});

test('guru tidak dapat menilai siswa yang belum mengumpulkan', function (): void {
    $this->actingAs($this->guruUser)
        ->put(route('app.guru.tugas.nilai', $this->tugas), [
            'siswa_nisn' => $this->siswa->nisn,
            'nilai' => 80,
        ])
        ->assertRedirect()
        ->assertSessionHas('inertia');
});

test('guru lain tidak dapat menilai tugas orang lain', function (): void {
    $guruLain = Guru::factory()->create();
    $guruLainUser = User::factory()->create(['guru_id' => $guruLain->id]);

    $this->actingAs($guruLainUser)
        ->put(route('app.guru.tugas.nilai', $this->tugas), [
            'siswa_nisn' => $this->siswa->nisn,
            'nilai' => 80,
        ])
        ->assertNotFound();
});

test('siswa mengumpulkan ulang setelah dinilai akan mereset nilai', function (): void {
    $this->tugas->update(['jenis_pengumpulan' => 'teks']);

    $pengumpulan = TugasPengumpulan::create([
        'tugas_id' => $this->tugas->id,
        'siswa_nisn' => $this->siswa->nisn,
        'jawaban_teks' => 'Jawaban lama',
        'submitted_at' => now(),
        'nilai' => 90,
    ]);

    $penilaian = Penilaian::factory()->create([
        'nama' => 'Tugas: '.$this->tugas->judul,
        'tipe' => 'tugas',
        'sumber' => 'tugas',
    ]);
    $this->tugas->update(['penilaian_id' => $penilaian->id]);

    DetailPenilaian::create([
        'penilaian_id' => $penilaian->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'siswa_nisn' => $this->siswa->nisn,
        'guru_id' => $this->guru->id,
        'nilai' => 90,
        'sumber' => 'tugas',
    ]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.tugas.kumpul', $this->tugas), [
            'jawaban_teks' => 'Jawaban baru',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($pengumpulan->fresh()->nilai)->toBeNull();

    expect(DetailPenilaian::query()
        ->where('penilaian_id', $penilaian->id)
        ->where('siswa_nisn', $this->siswa->nisn)
        ->exists())->toBeFalse();
});

test('menghapus tugas ikut menghapus penilaian sumber tugas', function (): void {
    $penilaian = Penilaian::factory()->create([
        'nama' => 'Tugas: '.$this->tugas->judul,
        'tipe' => 'tugas',
        'sumber' => 'tugas',
    ]);
    $this->tugas->update(['penilaian_id' => $penilaian->id]);

    $this->actingAs($this->guruUser)
        ->delete(route('app.guru.tugas.destroy', $this->tugas))
        ->assertRedirect();

    expect(Tugas::find($this->tugas->id))->toBeNull()
        ->and(Penilaian::find($penilaian->id))->toBeNull();
});

test('halaman pengumpulan menampilkan nilai dan poin', function (): void {
    TugasPengumpulan::create([
        'tugas_id' => $this->tugas->id,
        'siswa_nisn' => $this->siswa->nisn,
        'submitted_at' => now(),
        'nilai' => 75,
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.pengumpulan', $this->tugas))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Tugas/Pengumpulan')
            ->where('tugas.poin', 100)
            ->where('siswas.0.nilai', 75));
});
