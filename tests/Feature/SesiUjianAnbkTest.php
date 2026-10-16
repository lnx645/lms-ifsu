<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Penilaian;
use App\Models\SesiUjian;
use App\Models\SesiUjianPeserta;
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

function ujianAnbkSiap($ctx, string $mode = 'terjadwal'): Ujian
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
        'tanggal_selesai' => now()->addHours(8),
        'token' => null,
        'mode_sesi' => $mode,
    ]);
}

function sesiAnbk(Ujian $ujian, string $nama, int $mulaiMenit, int $selesaiMenit): SesiUjian
{
    return SesiUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'nama' => $nama,
        'waktu_mulai' => now()->addMinutes($mulaiMenit),
        'waktu_selesai' => now()->addMinutes($selesaiMenit),
    ]);
}

test('sesi tidak boleh tumpang tindih', function (): void {
    $ujian = ujianAnbkSiap($this, 'bebas');
    sesiAnbk($ujian, 'Sesi 1', -60, 60);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.sesi.store', $ujian), [
            'nama' => 'Sesi 2',
            'waktu_mulai' => now()->addMinutes(30)->format('Y-m-d H:i'),
            'waktu_selesai' => now()->addMinutes(90)->format('Y-m-d H:i'),
        ])
        ->assertSessionHasErrors('waktu_mulai');

    expect(SesiUjian::where('ujian_id', $ujian->id)->count())->toBe(1);
});

test('sesi berurutan tanpa overlap boleh dibuat', function (): void {
    $ujian = ujianAnbkSiap($this, 'bebas');
    sesiAnbk($ujian, 'Sesi 1', -120, -60);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.sesi.store', $ujian), [
            'nama' => 'Sesi 2',
            'waktu_mulai' => now()->addMinutes(60)->format('Y-m-d H:i'),
            'waktu_selesai' => now()->addMinutes(120)->format('Y-m-d H:i'),
        ])
        ->assertRedirect();

    expect(SesiUjian::where('ujian_id', $ujian->id)->count())->toBe(2);
});

test('siswa tanpa jatah sesi ditolak pada mode terjadwal', function (): void {
    $ujian = ujianAnbkSiap($this, 'terjadwal');
    sesiAnbk($ujian, 'Sesi 1', -30, 60);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect();

    expect(UjianPengerjaan::where('ujian_id', $ujian->id)->count())->toBe(0);
});

test('siswa hanya bisa masuk sesi jatahnya yang sedang aktif', function (): void {
    $ujian = ujianAnbkSiap($this, 'terjadwal');
    $sesi1 = sesiAnbk($ujian, 'Sesi 1', -30, 60);
    $sesi2 = sesiAnbk($ujian, 'Sesi 2', 120, 180);

    SesiUjianPeserta::create([
        'sesi_ujian_id' => $sesi2->id,
        'ujian_id' => $ujian->id,
        'siswa_nisn' => $this->siswa->nisn,
    ]);

    // Sesi jatah (Sesi 2) belum aktif -> ditolak.
    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect();

    expect(UjianPengerjaan::where('ujian_id', $ujian->id)->count())->toBe(0);

    // Pindahkan jatah ke Sesi 1 yang aktif -> boleh masuk.
    SesiUjianPeserta::where('ujian_id', $ujian->id)
        ->where('siswa_nisn', $this->siswa->nisn)
        ->update(['sesi_ujian_id' => $sesi1->id]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect(route('app.siswa.ujian.kerjakan', $ujian));

    $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)->first();
    expect($pengerjaan->sesi_ujian_id)->toBe($sesi1->id);
});

test('batas pengerjaan dipotong akhir sesi', function (): void {
    $ujian = ujianAnbkSiap($this, 'terjadwal');
    // Sesi berakhir 20 menit lagi, durasi ujian 60 menit.
    $sesi = sesiAnbk($ujian, 'Sesi 1', -30, 20);

    SesiUjianPeserta::create([
        'sesi_ujian_id' => $sesi->id,
        'ujian_id' => $ujian->id,
        'siswa_nisn' => $this->siswa->nisn,
    ]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect();

    $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)->first();
    expect($pengerjaan)->not->toBeNull();
    // Batas tidak boleh melewati akhir sesi (toleransi 2 menit untuk eksekusi).
    expect($pengerjaan->batas_at->lessThanOrEqualTo($sesi->waktu_selesai->addMinutes(2)))->toBeTrue();
    expect($pengerjaan->batas_at->diffInMinutes(now()->addMinutes(60)))->toBeGreaterThan(30);
});

test('guru dapat menjadwalkan dan membatalkan peserta sesi', function (): void {
    $ujian = ujianAnbkSiap($this, 'terjadwal');
    $sesi = sesiAnbk($ujian, 'Sesi 1', -30, 60);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.sesi.peserta.store', [$ujian, $sesi]), [
            'nisn' => [$this->siswa->nisn],
        ])
        ->assertRedirect();

    expect(SesiUjianPeserta::where('ujian_id', $ujian->id)
        ->where('siswa_nisn', $this->siswa->nisn)
        ->exists())->toBeTrue();

    $this->actingAs($this->guruUser)
        ->delete(route('app.guru.ujian.sesi.peserta.destroy', [$ujian, $sesi, $this->siswa->nisn]))
        ->assertRedirect();

    expect(SesiUjianPeserta::where('ujian_id', $ujian->id)->count())->toBe(0);
});

test('guru dapat mengubah mode sesi ujian', function (): void {
    $ujian = ujianAnbkSiap($this, 'bebas');

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.ujian.sesi.mode', $ujian), ['mode_sesi' => 'terjadwal'])
        ->assertRedirect();

    expect($ujian->fresh()->mode_sesi)->toBe('terjadwal');
});

test('pesertaMap menampilkan semua siswa dalam satu sesi', function (): void {
    $ujian = ujianAnbkSiap($this, 'terjadwal');
    $sesi = sesiAnbk($ujian, 'Sesi 1', -30, 60);

    // Buat 3 siswa tambahan.
    $nisns = [$this->siswa->nisn];
    for ($i = 0; $i < 2; $i++) {
        $s = Siswa::factory()->create();
        SiswaKelas::create([
            'siswa_nisn' => $s->nisn,
            'kelas_id' => $this->guruKelas->kelas_id,
            'tahun_ajaran_id' => $this->guruKelas->tahun_ajaran_id,
            'active' => true,
        ]);
        $nisns[] = $s->nisn;
    }

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.sesi.peserta.store', [$ujian, $sesi]), [
            'nisn' => $nisns,
        ])
        ->assertRedirect();

    // Halaman sesi harus menampilkan ketiga NISN di pesertaMap sesi tersebut.
    $response = $this->actingAs($this->guruUser)
        ->get(route('app.guru.ujian.sesi.index', $ujian))
        ->assertOk();

    $pesertaMap = $response->viewData('page')['props']['pesertaMap'] ?? null;
    if ($pesertaMap === null) {
        $pesertaMap = json_decode($response->getContent(), true)['props']['pesertaMap'] ?? [];
    }
    $got = $pesertaMap[$sesi->id] ?? $pesertaMap[(string) $sesi->id] ?? [];
    sort($got);
    $expected = $nisns;
    sort($expected);
    expect($got)->toBe($expected);
});
