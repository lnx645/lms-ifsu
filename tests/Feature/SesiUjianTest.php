<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Penilaian;
use App\Models\SesiUjian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Models\User;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'IPA']);
    $this->kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);

    $this->guru = Guru::factory()->create();
    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->siswa = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswa->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $this->siswaUser = User::factory()->create(['nisn' => $this->siswa->nisn]);
});

function ujianSiapSesi($ctx): Ujian
{
    $penilaian = Penilaian::create([
        'nama' => 'CBT', 'tipe' => 'cbt', 'nilai_maks' => 100, 'bobot' => 1, 'aktif' => true, 'sumber' => 'kuis',
    ]);

    return Ujian::factory()->create([
        'guru_id' => $ctx->guru->id,
        'guru_kelas_id' => $ctx->guruKelas->id,
        'penilaian_id' => $penilaian->id,
        'status' => 'terbit',
        'nilai_maks' => 100,
        'durasi_menit' => 60,
        'tanggal_mulai' => now()->subHour(),
        'tanggal_selesai' => now()->addHours(5),
        'token' => null,
    ]);
}

test('guru dapat menambah sesi ujian', function (): void {
    $ujian = ujianSiapSesi($this);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.sesi.store', $ujian), [
            'nama' => 'Sesi 1',
            'waktu_mulai' => now()->subMinutes(30)->format('Y-m-d H:i'),
            'waktu_selesai' => now()->addHour()->format('Y-m-d H:i'),
            'kapasitas' => 30,
        ])
        ->assertRedirect();

    expect(SesiUjian::where('ujian_id', $ujian->id)->count())->toBe(1);
});

test('sesi tidak boleh keluar dari jadwal ujian', function (): void {
    $ujian = ujianSiapSesi($this);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.sesi.store', $ujian), [
            'nama' => 'Sesi X',
            'waktu_mulai' => now()->addHours(10)->format('Y-m-d H:i'),
            'waktu_selesai' => now()->addHours(11)->format('Y-m-d H:i'),
        ])
        ->assertSessionHasErrors('waktu_selesai');

    expect(SesiUjian::where('ujian_id', $ujian->id)->count())->toBe(0);
});

test('siswa tidak dapat mulai saat tidak ada sesi aktif', function (): void {
    $ujian = ujianSiapSesi($this);
    SesiUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'nama' => 'Sesi 1',
        'waktu_mulai' => now()->addHours(2),
        'waktu_selesai' => now()->addHours(3),
    ]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect();

    expect(UjianPengerjaan::where('ujian_id', $ujian->id)->count())->toBe(0);
});

test('siswa dapat mulai di sesi aktif dan pengerjaan tertaut ke sesi', function (): void {
    $ujian = ujianSiapSesi($this);
    $sesi = SesiUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'nama' => 'Sesi 1',
        'waktu_mulai' => now()->subMinutes(30),
        'waktu_selesai' => now()->addHour(),
    ]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect();

    $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)->first();
    expect($pengerjaan)->not->toBeNull()
        ->and($pengerjaan->sesi_ujian_id)->toBe($sesi->id);
});

test('sesi penuh menolak siswa baru', function (): void {
    $ujian = ujianSiapSesi($this);
    $sesi = SesiUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'waktu_mulai' => now()->subMinutes(30),
        'waktu_selesai' => now()->addHour(),
        'kapasitas' => 1,
    ]);

    // Isi satu slot dengan pengerjaan berlangsung.
    $siswaLain = Siswa::factory()->create();
    UjianPengerjaan::factory()->create([
        'ujian_id' => $ujian->id,
        'siswa_nisn' => $siswaLain->nisn,
        'sesi_ujian_id' => $sesi->id,
        'status' => 'berlangsung',
        'mulai_at' => now()->subMinutes(10),
        'batas_at' => now()->addMinutes(50),
    ]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect();

    expect(UjianPengerjaan::where('ujian_id', $ujian->id)
        ->where('siswa_nisn', $this->siswa->nisn)->count())->toBe(0);
});

test('ujian tanpa sesi tetap berjalan seperti biasa', function (): void {
    $ujian = ujianSiapSesi($this);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect();

    $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)->first();
    expect($pengerjaan)->not->toBeNull()
        ->and($pengerjaan->sesi_ujian_id)->toBeNull();
});

test('endpoint waktu-server mengembalikan waktu valid', function (): void {
    $res = $this->actingAs($this->siswaUser)
        ->getJson(route('app.siswa.waktu-server'))
        ->assertOk();

    $unix = $res->json('unix');
    expect($unix)->toBeInt()
        ->and(abs($unix - now()->timestamp))->toBeLessThan(5);
});
