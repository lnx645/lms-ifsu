<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\PeriodeUjian;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
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
});

function payloadUjian(array $overrides = []): array
{
    return array_merge([
        'kategori' => 'kuis',
        'judul' => 'Kuis Bab 1',
        'deskripsi' => 'Kuis singkat',
        'durasi_menit' => 30,
        'maks_attempt' => 1,
        'acak_soal' => false,
        'acak_opsi' => false,
        'tampilkan_hasil' => true,
        'wajib_fullscreen' => false,
        'maks_pelanggaran' => 0,
        'nilai_maks' => 100,
        'bobot' => 1,
    ], $overrides);
}

test('guru dapat membuka halaman ujian', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.ujian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Ujian/Index')
            ->has('penugasan', 1));
});

test('guru dapat membuat kuis dan penilaian otomatis dibuat', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.store'), payloadUjian([
            'guru_kelas_id' => $this->guruKelas->id,
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $ujian = Ujian::where('judul', 'Kuis Bab 1')->first();

    expect($ujian)->not->toBeNull()
        ->and($ujian->guru_id)->toBe($this->guru->id)
        ->and($ujian->penilaian_id)->not->toBeNull();

    $this->assertDatabaseHas('penilaian', [
        'id' => $ujian->penilaian_id,
        'sumber' => 'kuis',
        'tipe' => 'cbt',
    ]);
});

test('guru tidak dapat membuat ujian di penugasan guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.store'), payloadUjian([
            'guru_kelas_id' => $guruKelasLain->id,
        ]))
        ->assertSessionHasErrors('guru_kelas_id');
});

test('ujian besar wajib dijadwalkan dalam periode admin', function (): void {
    // Tanpa periode -> ditolak
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.store'), payloadUjian([
            'guru_kelas_id' => $this->guruKelas->id,
            'kategori' => 'uas',
            'judul' => 'UAS Ganjil',
            'tanggal_mulai' => now()->addDay()->format('Y-m-d H:i'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d H:i'),
        ]))
        ->assertSessionHasErrors('tanggal_mulai');

    // Dengan periode yang mencakup -> lolos
    PeriodeUjian::create([
        'kategori' => 'uas',
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'nama' => 'UAS Ganjil',
        'tanggal_mulai' => now(),
        'tanggal_selesai' => now()->addWeek(),
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.store'), payloadUjian([
            'guru_kelas_id' => $this->guruKelas->id,
            'kategori' => 'uas',
            'judul' => 'UAS Ganjil',
            'tanggal_mulai' => now()->addDay()->format('Y-m-d H:i'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d H:i'),
        ]))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('ujians', ['judul' => 'UAS Ganjil', 'kategori' => 'uas']);
});

test('guru dapat menambah soal pilihan ganda', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.store', $ujian), [
            'tipe' => 'pg',
            'pertanyaan' => '<p>2 + 2 = ?</p>',
            'poin' => 10,
            'opsi' => [
                ['teks' => '3', 'benar' => false],
                ['teks' => '4', 'benar' => true],
                ['teks' => '5', 'benar' => false],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $soal = Soal::where('ujian_id', $ujian->id)->first();
    expect($soal)->not->toBeNull()
        ->and($soal->opsi)->toHaveCount(3)
        ->and($soal->opsi->where('benar', true))->toHaveCount(1);
});

test('soal pilihan ganda harus punya tepat satu jawaban benar', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.store', $ujian), [
            'tipe' => 'pg',
            'pertanyaan' => '<p>Soal</p>',
            'poin' => 10,
            'opsi' => [
                ['teks' => 'A', 'benar' => true],
                ['teks' => 'B', 'benar' => true],
            ],
        ])
        ->assertSessionHasErrors('opsi');
});

test('guru dapat menambah soal esai walau payload menyertakan opsi kosong', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.store', $ujian), [
            'tipe' => 'esai',
            'pertanyaan' => '<p>Jelaskan proses fotosintesis.</p>',
            'poin' => 20,
            'opsi' => [
                ['teks' => '', 'benar' => true],
                ['teks' => '', 'benar' => false],
            ],
            'kunci_isian' => [''],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $soal = Soal::where('ujian_id', $ujian->id)->first();
    expect($soal)->not->toBeNull()
        ->and($soal->tipe)->toBe('esai')
        ->and($soal->opsi()->count())->toBe(0)
        ->and($soal->kunci_isian)->toBeNull();
});

test('guru dapat menambah soal isian dengan kunci jawaban', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.store', $ujian), [
            'tipe' => 'isian',
            'pertanyaan' => '<p>Ibukota Indonesia?</p>',
            'poin' => 5,
            'opsi' => [
                ['teks' => '', 'benar' => true],
                ['teks' => '', 'benar' => false],
            ],
            'kunci_isian' => ['Jakarta', '  '],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $soal = Soal::where('ujian_id', $ujian->id)->first();
    expect($soal->tipe)->toBe('isian')
        ->and($soal->kunci_isian)->toBe(['Jakarta'])
        ->and($soal->opsi()->count())->toBe(0);
});

test('soal isian wajib punya kunci jawaban', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.store', $ujian), [
            'tipe' => 'isian',
            'pertanyaan' => '<p>Ibukota Indonesia?</p>',
            'poin' => 5,
            'kunci_isian' => [],
        ])
        ->assertSessionHasErrors('kunci_isian');
});

test('guru tidak dapat menerbitkan ujian tanpa soal', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'status' => 'draft',
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.terbit', $ujian))
        ->assertRedirect();

    expect($ujian->fresh()->status)->toBe('draft');
});

test('guru dapat menerbitkan ujian yang memiliki soal', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'status' => 'draft',
    ]);
    Soal::factory()->for($ujian)->create();

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.terbit', $ujian))
        ->assertRedirect();

    expect($ujian->fresh()->status)->toBe('terbit');
});

test('guru dapat membuat dan merilis token', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.token', $ujian))
        ->assertRedirect();

    $ujian->refresh();
    expect($ujian->token)->not->toBeNull()
        ->and($ujian->token_released_at)->toBeNull();

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.token.toggle', $ujian))
        ->assertRedirect();

    expect($ujian->fresh()->token_released_at)->not->toBeNull();
});

test('guru tidak dapat mengelola token bila pengelola diatur admin', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'token_pengelola' => 'admin',
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.token', $ujian))
        ->assertRedirect();

    expect($ujian->fresh()->token)->toBeNull();
});

test('guru tidak dapat mengelola ujian milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $ujian = Ujian::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.ujian.soal.index', $ujian))
        ->assertNotFound();
});

test('halaman guru ujian memblokir siswa', function (): void {
    $siswaUser = User::factory()->create(['nisn' => Siswa::factory()->create()->nisn]);

    $this->actingAs($siswaUser)
        ->get(route('app.guru.ujian.index'))
        ->assertForbidden();
});
