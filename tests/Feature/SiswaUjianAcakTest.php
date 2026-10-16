<?php

use App\Models\Guru;
use App\Models\GuruKelas;
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
use Illuminate\Support\Collection;

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

    $this->siswa = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswa->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $this->siswaUser = User::factory()->create(['nisn' => $this->siswa->nisn]);
});

/**
 * Buat ujian terbit dengan sejumlah soal PG berurutan, masing-masing 4 opsi.
 *
 * @return array{0: Ujian, 1: Collection<int, Soal>}
 */
function ujianDenganBanyakSoal($ctx, int $jumlahSoal, array $ujianOverrides = []): array
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

    $soals = collect();
    for ($i = 1; $i <= $jumlahSoal; $i++) {
        $soal = Soal::factory()->for($ujian)->create([
            'urutan' => $i,
            'pertanyaan' => "<p>Soal nomor {$i}</p>",
        ]);
        OpsiSoal::factory()->for($soal)->create(['teks' => 'Opsi A', 'benar' => true, 'urutan' => 0]);
        OpsiSoal::factory()->for($soal)->create(['teks' => 'Opsi B', 'benar' => false, 'urutan' => 1]);
        OpsiSoal::factory()->for($soal)->create(['teks' => 'Opsi C', 'benar' => false, 'urutan' => 2]);
        OpsiSoal::factory()->for($soal)->create(['teks' => 'Opsi D', 'benar' => false, 'urutan' => 3]);
        $soals->push($soal);
    }

    return [$ujian, $soals];
}

/**
 * Ambil ID soal seperti yang dikirim ke siswa (sesudah acak/urut).
 *
 * @return array<int, int>
 */
function soalIdsUntukSiswa($test, Ujian $ujian): array
{
    $ids = null;

    $test->actingAs($test->siswaUser)
        ->get(route('app.siswa.ujian.kerjakan', $ujian))
        ->assertOk()
        ->assertInertia(function ($page) use (&$ids): void {
            $ids = collect($page->toArray()['props']['soals'])->pluck('id')->all();
        });

    return $ids ?? [];
}

test('acak soal nonaktif menampilkan soal sesuai urutan urutan', function (): void {
    [$ujian] = ujianDenganBanyakSoal($this, 5, ['acak_soal' => false]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true])
        ->assertRedirect(route('app.siswa.ujian.kerjakan', $ujian));

    $expected = Soal::where('ujian_id', $ujian->id)->orderBy('urutan')->pluck('id')->all();

    expect(soalIdsUntukSiswa($this, $ujian))->toBe($expected);
});

/**
 * Ambil opsi soal pertama seperti yang dikirim ke siswa (sesudah acak/urut).
 *
 * @return array<int, array{id: int, teks: string}>
 */
function opsiPertamaUntukSiswa($test, Ujian $ujian): array
{
    $opsi = null;

    $test->actingAs($test->siswaUser)
        ->get(route('app.siswa.ujian.kerjakan', $ujian))
        ->assertOk()
        ->assertInertia(function ($page) use (&$opsi): void {
            $opsi = collect($page->toArray()['props']['soals'])->first()['opsi'];
        });

    return $opsi ?? [];
}

test('acak opsi nonaktif menampilkan opsi sesuai urutan', function (): void {
    [$ujian, $soals] = ujianDenganBanyakSoal($this, 2, ['acak_opsi' => false]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

    $urutanAsli = OpsiSoal::where('soal_id', $soals->first()->id)
        ->orderBy('urutan')
        ->pluck('id')
        ->all();

    $opsiTampil = opsiPertamaUntukSiswa($this, $ujian);

    expect(collect($opsiTampil)->pluck('id')->all())->toBe($urutanAsli);
});

test('acak soal aktif tetap menampilkan semua soal tanpa ada yang hilang', function (): void {
    [$ujian, $soals] = ujianDenganBanyakSoal($this, 8, ['acak_soal' => true]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

    $ids = soalIdsUntukSiswa($this, $ujian);

    expect($ids)->toHaveCount(8);
    expect(array_unique($ids))->toHaveCount(8);
    expect(array_diff($soals->pluck('id')->all(), $ids))->toBeEmpty();
});

test('acak opsi aktif tetap menampilkan semua opsi tanpa ada yang hilang', function (): void {
    [$ujian, $soals] = ujianDenganBanyakSoal($this, 3, ['acak_opsi' => true]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

    $opsiAsli = OpsiSoal::where('soal_id', $soals->first()->id)->pluck('id')->all();
    $opsiTampil = opsiPertamaUntukSiswa($this, $ujian);

    expect($opsiTampil)->toHaveCount(4);
    expect(array_diff($opsiAsli, collect($opsiTampil)->pluck('id')->all()))->toBeEmpty();
});

test('acak soal aktif menghasilkan urutan stabil dalam satu attempt', function (): void {
    [$ujian] = ujianDenganBanyakSoal($this, 8, ['acak_soal' => true]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

    $first = soalIdsUntukSiswa($this, $ujian);
    $second = soalIdsUntukSiswa($this, $ujian);

    expect($first)->toBe($second);
});

test('acak opsi aktif menghasilkan urutan stabil dalam satu attempt', function (): void {
    [$ujian] = ujianDenganBanyakSoal($this, 2, ['acak_opsi' => true]);

    $this->actingAs($this->siswaUser)
        ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

    $first = collect(opsiPertamaUntukSiswa($this, $ujian))->pluck('id')->all();
    $second = collect(opsiPertamaUntukSiswa($this, $ujian))->pluck('id')->all();

    expect($first)->toBe($second);
});

test('acak soal aktif menghasilkan urutan berbeda antar attempt', function (): void {
    // Banyak soal + maks attempt > 1 agar kemungkinan beda urutan tinggi.
    [$ujian] = ujianDenganBanyakSoal($this, 10, [
        'acak_soal' => true,
        'maks_attempt' => 2,
    ]);

    $urutanPerAttempt = [];

    for ($i = 1; $i <= 2; $i++) {
        $this->actingAs($this->siswaUser)
            ->post(route('app.siswa.ujian.mulai', $ujian), ['setuju_tata_tertib' => true]);

        $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('siswa_nisn', $this->siswa->nisn)
            ->latest('id')
            ->first();

        expect($pengerjaan->attempt_ke)->toBe($i);

        $urutanPerAttempt[] = soalIdsUntukSiswa($this, $ujian);

        // Submit agar attempt berikutnya bisa mulai.
        $this->actingAs($this->siswaUser)
            ->post(route('app.siswa.ujian.submit', $ujian));
    }

    // Dengan 10 soal & seed berbeda, urutan antar attempt harus beda.
    expect($urutanPerAttempt[0])->not->toBe($urutanPerAttempt[1]);
});
