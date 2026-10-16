<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\OpsiSoal;
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
    $this->matpel = Matpel::factory()->create(['name' => 'IPA']);
    $this->kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);
    $this->kelasLain = Kelas::factory()->create(['nama' => 'X-RPL-2']);

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
    $this->siswaUser = User::factory()->create(['nisn' => $this->siswa->nisn]);

    $this->siswaLuar = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswaLuar->nisn,
        'kelas_id' => $this->kelasLain->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $this->siswaLuarUser = User::factory()->create(['nisn' => $this->siswaLuar->nisn]);
});

/**
 * Buat ujian terbit dengan satu soal PG + penilaian.
 */
function ujianTerbitDenganSoal($ctx, array $ujianOverrides = []): array
{
    $penilaian = Penilaian::create([
        'nama' => 'CBT', 'tipe' => 'cbt', 'nilai_maks' => 100, 'bobot' => 1, 'aktif' => true, 'sumber' => 'kuis',
    ]);

    $ujian = Ujian::factory()->create(array_merge([
        'guru_id' => $ctx->guru->id,
        'guru_kelas_id' => $ctx->guruKelas->id,
        'penilaian_id' => $penilaian->id,
        'status' => 'terbit',
        'nilai_maks' => 100,
        'durasi_menit' => 60,
        'tanggal_mulai' => now()->subHour(),
        'tanggal_selesai' => now()->addHour(),
    ], $ujianOverrides));

    $soal = Soal::factory()->for($ujian)->create(['tipe' => 'pg', 'poin' => 10]);
    $benar = OpsiSoal::factory()->for($soal)->create(['teks' => 'Benar', 'benar' => true]);
    OpsiSoal::factory()->for($soal)->create(['teks' => 'Salah', 'benar' => false]);

    return [$ujian, $soal, $benar];
}

test('siswa melihat daftar ujian terbit kelasnya', function (): void {
    [$ujian] = ujianTerbitDenganSoal($this);

    $this->actingAs($this->siswaUser)
        ->get(route('app.siswa.ujian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Ujian/Index')
            ->has('ujians', 1)
            ->where('ujians.0.judul', $ujian->judul));
});

test('siswa kelas lain tidak melihat ujian', function (): void {
    ujianTerbitDenganSoal($this);

    $this->actingAs($this->siswaLuarUser)
        ->get(route('app.siswa.ujian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('ujians', 0));
});

test('siswa dapat memulai ujian tanpa token', function (): void {
    [$ujian] = ujianTerbitDenganSoal($this);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect(route('app.siswa.ujian.kerjakan', $ujian));

    $this->assertDatabaseHas('ujian_pengerjaans', [
        'ujian_id' => $ujian->id,
        'siswa_nisn' => $this->siswa->nisn,
        'status' => 'berlangsung',
    ]);
});

test('siswa tidak dapat memulai tanpa token yang benar', function (): void {
    [$ujian] = ujianTerbitDenganSoal($this, ['token' => 'ABC123', 'token_released_at' => now()]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['token' => 'SALAH1', 'setuju_tata_tertib' => true])
        ->assertRedirect();

    $this->assertDatabaseMissing('ujian_pengerjaans', ['ujian_id' => $ujian->id]);

    // Token benar (case-insensitive)
    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['token' => 'abc123', 'setuju_tata_tertib' => true])
        ->assertRedirect(route('app.siswa.ujian.kerjakan', $ujian));

    $this->assertDatabaseHas('ujian_pengerjaans', ['ujian_id' => $ujian->id]);
});

test('token yang belum dirilis menolak siswa', function (): void {
    [$ujian] = ujianTerbitDenganSoal($this, ['token' => 'ABC123', 'token_released_at' => null]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['token' => 'ABC123', 'setuju_tata_tertib' => true])
        ->assertRedirect();

    $this->assertDatabaseMissing('ujian_pengerjaans', ['ujian_id' => $ujian->id]);
});

test('siswa dapat menyimpan jawaban lalu submit dan objektif dinilai otomatis', function (): void {
    [$ujian, $soal, $benar] = ujianTerbitDenganSoal($this);

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.jawaban', $ujian), [
            'soal_id' => $soal->id,
            'opsi_dipilih' => [$benar->id],
        ])
        ->assertRedirect();

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.submit', $ujian))
        ->assertRedirect(route('app.siswa.ujian.hasil', $ujian));

    $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)->first();
    expect($pengerjaan->status)->toBe('selesai')
        ->and($pengerjaan->nilai_total)->toBe(100.0);

    // Nilai tersinkron ke detail_penilaian
    $this->assertDatabaseHas('detail_penilaian', [
        'penilaian_id' => $ujian->penilaian_id,
        'siswa_nisn' => $this->siswa->nisn,
        'nilai' => 100,
        'sumber' => 'kuis',
    ]);
});

test('jawaban salah mendapat nilai nol', function (): void {
    [$ujian, $soal] = ujianTerbitDenganSoal($this);
    $salah = OpsiSoal::where('soal_id', $soal->id)->where('benar', false)->first();

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.jawaban', $ujian), [
        'soal_id' => $soal->id,
        'opsi_dipilih' => [$salah->id],
    ]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.submit', $ujian));

    expect(UjianPengerjaan::where('ujian_id', $ujian->id)->first()->nilai_total)->toBe(0.0);
});

test('soal isian dinilai otomatis case-insensitive', function (): void {
    $penilaian = Penilaian::create([
        'nama' => 'CBT', 'tipe' => 'cbt', 'nilai_maks' => 100, 'bobot' => 1, 'aktif' => true, 'sumber' => 'kuis',
    ]);
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'penilaian_id' => $penilaian->id,
        'status' => 'terbit',
        'tanggal_mulai' => now()->subHour(),
        'tanggal_selesai' => now()->addHour(),
    ]);
    $soal = Soal::factory()->for($ujian)->isian(['Jakarta'])->create(['poin' => 10]);

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.jawaban', $ujian), [
        'soal_id' => $soal->id,
        'jawaban_teks' => '  jakarta ',
    ]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.submit', $ujian));

    expect(UjianPengerjaan::where('ujian_id', $ujian->id)->first()->nilai_total)->toBe(100.0);
});

test('ujian dengan esai menunggu koreksi guru sebelum nilai final', function (): void {
    $penilaian = Penilaian::create([
        'nama' => 'CBT', 'tipe' => 'cbt', 'nilai_maks' => 100, 'bobot' => 1, 'aktif' => true, 'sumber' => 'uts',
    ]);
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'penilaian_id' => $penilaian->id,
        'kategori' => 'uts',
        'status' => 'terbit',
        'tanggal_mulai' => now()->subHour(),
        'tanggal_selesai' => now()->addHour(),
    ]);
    $soal = Soal::factory()->for($ujian)->esai()->create(['poin' => 10]);

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.jawaban', $ujian), [
        'soal_id' => $soal->id,
        'jawaban_teks' => 'Jawaban esai panjang',
    ]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.submit', $ujian));

    $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)->first();
    expect($pengerjaan->nilai_total)->toBeNull();
    $this->assertDatabaseMissing('detail_penilaian', ['penilaian_id' => $penilaian->id]);
});

test('waktu habis membuat pengerjaan auto submit', function (): void {
    [$ujian, $soal, $benar] = ujianTerbitDenganSoal($this);

    $pengerjaan = UjianPengerjaan::create([
        'ujian_id' => $ujian->id,
        'siswa_nisn' => $this->siswa->nisn,
        'attempt_ke' => 1,
        'mulai_at' => now()->subHours(2),
        'batas_at' => now()->subHour(),
        'status' => 'berlangsung',
    ]);
    JawabanSiswa::create([
        'ujian_pengerjaan_id' => $pengerjaan->id,
        'soal_id' => $soal->id,
        'opsi_dipilih' => [$benar->id],
    ]);

    $this->actingAs($this->siswaUser)
        ->get(route('app.siswa.ujian.kerjakan', $ujian))
        ->assertRedirect(route('app.siswa.ujian.index'));

    expect($pengerjaan->fresh()->status)->toBe('auto_submit');
});

test('batas percobaan menghalangi memulai ulang', function (): void {
    [$ujian] = ujianTerbitDenganSoal($this, ['maks_attempt' => 1]);

    UjianPengerjaan::create([
        'ujian_id' => $ujian->id,
        'siswa_nisn' => $this->siswa->nisn,
        'attempt_ke' => 1,
        'mulai_at' => now()->subHour(),
        'batas_at' => now()->subMinutes(30),
        'submitted_at' => now()->subMinutes(30),
        'status' => 'selesai',
    ]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect();

    expect(UjianPengerjaan::where('ujian_id', $ujian->id)->count())->toBe(1);
});

test('pelanggaran melebihi ambang mendiskualifikasi siswa', function (): void {
    [$ujian] = ujianTerbitDenganSoal($this, ['maks_pelanggaran' => 2]);

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.lapor', $ujian), ['jenis' => 'blur']);
    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.lapor', $ujian), ['jenis' => 'exit_fullscreen'])
        ->assertRedirect(route('app.siswa.ujian.index'));

    $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)->first();
    expect($pengerjaan->status)->toBe('diskualifikasi')
        ->and($pengerjaan->jumlah_pelanggaran)->toBe(2);
    $this->assertDatabaseCount('pelanggaran_logs', 2);
});

test('nilai ujian muncul di halaman nilai siswa', function (): void {
    [$ujian, $soal, $benar] = ujianTerbitDenganSoal($this);

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.jawaban', $ujian), [
        'soal_id' => $soal->id,
        'opsi_dipilih' => [$benar->id],
    ]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.submit', $ujian));

    $this->actingAs($this->siswaUser)
        ->get(route('app.siswa.penilaian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Penilaian/Index')
            ->where('matpel', fn ($matpel) => collect($matpel)->contains(
                fn ($m) => collect($m['nilai'])->contains(
                    fn ($n) => $n['sumber'] === 'kuis' && (float) $n['nilai'] === 100.0,
                ),
            )));
});

test('siswa tidak dapat memulai ujian kelas lain', function (): void {
    [$ujian] = ujianTerbitDenganSoal($this);

    $this->actingAs($this->siswaLuarUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertNotFound();
});

test('halaman siswa ujian memblokir guru', function (): void {
    $guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->actingAs($guruUser)
        ->get(route('app.siswa.ujian.index'))
        ->assertForbidden();
});

test('siswa dapat menandai jawaban ragu-ragu dan membatalkannya', function (): void {
    [$ujian, $soal, $benar] = ujianTerbitDenganSoal($this);

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

    // Tandai ragu-ragu.
    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.jawaban', $ujian), [
            'soal_id' => $soal->id,
            'opsi_dipilih' => [$benar->id],
            'ragu' => true,
        ])
        ->assertRedirect();

    $jawaban = JawabanSiswa::where('soal_id', $soal->id)->first();
    expect($jawaban->ragu)->toBeTrue();

    // Batalkan tanda ragu-ragu.
    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.jawaban', $ujian), [
            'soal_id' => $soal->id,
            'opsi_dipilih' => [$benar->id],
            'ragu' => false,
        ])
        ->assertRedirect();

    expect($jawaban->fresh()->ragu)->toBeFalse();
});

test('status ragu tampil di data pengerjaan', function (): void {
    [$ujian, $soal, $benar] = ujianTerbitDenganSoal($this);

    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);
    $this->actingAs($this->siswaUser)->post(route('app.siswa.ujian.jawaban', $ujian), [
        'soal_id' => $soal->id,
        'opsi_dipilih' => [$benar->id],
        'ragu' => true,
    ]);

    $this->actingAs($this->siswaUser)
        ->get(route('app.siswa.ujian.kerjakan', $ujian))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('soals.0.jawaban.ragu', true));
});
