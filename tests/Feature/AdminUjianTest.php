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
    $this->matpel = Matpel::factory()->create(['name' => 'PKN']);
    $this->kelas = Kelas::factory()->create(['nama' => 'XII-RPL-1']);

    $this->guru = Guru::factory()->create(['nama_lengkap' => 'Bu Guru']);
    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('admin dapat membuka daftar periode ujian', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.periode-ujian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/PeriodeUjian/Index'));
});

test('admin dapat membuat periode ujian', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.periode-ujian.store'), [
            'kategori' => 'uas',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nama' => 'UAS Ganjil',
            'tanggal_mulai' => now()->format('Y-m-d H:i'),
            'tanggal_selesai' => now()->addWeek()->format('Y-m-d H:i'),
        ])
        ->assertRedirect(route('admin.periode-ujian.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('periode_ujians', ['nama' => 'UAS Ganjil', 'kategori' => 'uas']);
});

test('admin melihat semua ujian lintas guru', function (): void {
    Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'judul' => 'Kuis Guru',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.ujian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Ujian/Index')
            ->has('ujians.data', 1)
            ->where('ujians.data.0.guru', 'Bu Guru'));
});

test('admin dapat membuat ujian untuk penugasan guru mana pun', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.ujian.store'), [
            'guru_kelas_id' => $this->guruKelas->id,
            'kategori' => 'kuis',
            'judul' => 'Kuis Admin',
            'durasi_menit' => 30,
            'maks_attempt' => 1,
            'maks_pelanggaran' => 0,
            'nilai_maks' => 100,
            'bobot' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $ujian = Ujian::where('judul', 'Kuis Admin')->first();
    expect($ujian)->not->toBeNull()
        ->and($ujian->guru_id)->toBe($this->guru->id)
        ->and($ujian->dibuat_oleh_admin)->toBeTrue();
});

test('admin dapat menghapus ujian', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.ujian.destroy', $ujian))
        ->assertRedirect();

    $this->assertDatabaseMissing('ujians', ['id' => $ujian->id]);
});

test('admin dapat memantau soal & hasil serta menilai esai lintas guru', function (): void {
    $penilaian = Penilaian::create([
        'nama' => 'CBT', 'tipe' => 'cbt', 'nilai_maks' => 100, 'bobot' => 1, 'aktif' => true, 'sumber' => 'kuis',
    ]);
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'penilaian_id' => $penilaian->id,
        'status' => 'terbit',
    ]);
    $soal = Soal::factory()->for($ujian)->esai()->create(['poin' => 100]);

    $siswa = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $siswa->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $pengerjaan = UjianPengerjaan::create([
        'ujian_id' => $ujian->id,
        'siswa_nisn' => $siswa->nisn,
        'attempt_ke' => 1,
        'mulai_at' => now()->subHour(),
        'batas_at' => now()->subMinutes(30),
        'submitted_at' => now()->subMinutes(30),
        'status' => 'selesai',
    ]);
    $jawaban = JawabanSiswa::create([
        'ujian_pengerjaan_id' => $pengerjaan->id,
        'soal_id' => $soal->id,
        'jawaban_teks' => 'Jawaban',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.ujian.soal', $ujian))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Ujian/Soal')->has('soals', 1));

    $this->actingAs($this->admin)
        ->get(route('admin.ujian.hasil', $ujian))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Ujian/Hasil')->has('siswas', 1));

    $this->actingAs($this->admin)
        ->get(route('admin.ujian.hasil.show', [$ujian, $pengerjaan]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Ujian/Koreksi')->has('jawabans', 1));

    $this->actingAs($this->admin)
        ->post(route('admin.ujian.hasil.nilai', [$ujian, $pengerjaan, $jawaban]), ['skor' => 90])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($pengerjaan->fresh()->nilai_total)->toBe(90.0);
    $this->assertDatabaseHas('detail_penilaian', [
        'penilaian_id' => $penilaian->id,
        'siswa_nisn' => $siswa->nisn,
        'nilai' => 90,
    ]);
});

test('admin dapat mengatur pengelola token lalu membuat & merilis token', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'token_pengelola' => 'guru',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ujian.pengelola-token', $ujian), ['token_pengelola' => 'admin'])
        ->assertRedirect();
    expect($ujian->fresh()->token_pengelola)->toBe('admin');

    $this->actingAs($this->admin)
        ->post(route('admin.ujian.token', $ujian))
        ->assertRedirect();
    $ujian->refresh();
    expect($ujian->token)->not->toBeNull()
        ->and($ujian->token_released_at)->toBeNull();

    $this->actingAs($this->admin)
        ->post(route('admin.ujian.token.toggle', $ujian))
        ->assertRedirect();
    expect($ujian->fresh()->token_released_at)->not->toBeNull();
});

test('admin tidak dapat membuat token bila pengelola masih guru', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'token_pengelola' => 'guru',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.ujian.token', $ujian))
        ->assertRedirect();

    expect($ujian->fresh()->token)->toBeNull();
});

test('periode ujian admin memblokir guru', function (): void {
    $guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->actingAs($guruUser)
        ->get(route('admin.periode-ujian.index'))
        ->assertForbidden();
});
