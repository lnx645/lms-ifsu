<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\TugasPengumpulan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');

    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->kelasA = Kelas::factory()->create(['nama' => 'X-RPL-1']);
    $this->kelasB = Kelas::factory()->create(['nama' => 'X-RPL-2']);

    $this->guru = Guru::factory()->create();
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->guruKelasA = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->siswaA = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswaA->nisn,
        'kelas_id' => $this->kelasA->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $this->siswaAUser = User::factory()->create(['nisn' => $this->siswaA->nisn]);
});

test('guru dapat membuka halaman tugas', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Tugas/Index')
            ->has('penugasan', 1)
            ->where('penugasan.0.label', 'X-RPL-1 — Matematika')
            ->where('filters.guru_kelas_id', null));
});

test('halaman tugas hanya menampilkan tugas tahun ajaran aktif', function (): void {
    $tugasTahunIni = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas Tahun Ini',
    ]);

    $tahunLama = TahunAjaran::factory()->create(['active' => false]);
    $guruKelasLama = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $tahunLama->id,
        'aktif' => false,
    ]);
    Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $guruKelasLama->id,
        'judul' => 'Tugas Tahun Lalu',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Tugas/Index')
            ->has('tugases.data', 1)
            ->where('tugases.data.0.judul', 'Tugas Tahun Ini'));

    expect(Tugas::where('judul', 'Tugas Tahun Lalu')->exists())->toBeTrue();
});

test('guru dapat membuat tugas beserta berkasnya', function (): void {
    $file = UploadedFile::fake()->create('soal.pdf', 100, 'application/pdf');

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.tugas.store'), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Tugas 1: Persamaan Linear',
            'deskripsi' => 'Kerjakan di buku latihan.',
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
            'file' => $file,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $tugas = Tugas::query()->where('judul', 'Tugas 1: Persamaan Linear')->first();

    expect($tugas)->not->toBeNull()
        ->and($tugas->guru_id)->toBe($this->guru->id)
        ->and($tugas->guru_kelas_id)->toBe($this->guruKelasA->id)
        ->and($tugas->file_name)->toBe('soal.pdf')
        ->and($tugas->tanggal_terbit)->not->toBeNull();

    Storage::disk('public')->assertExists($tugas->file_path);
});

test('guru dapat menjadwalkan tugas dengan tanggal terbit di masa depan', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.tugas.store'), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Tugas Terjadwal',
            'tanggal_terbit' => now()->addDay()->format('Y-m-d H:i'),
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $tugas = Tugas::query()->where('judul', 'Tugas Terjadwal')->first();

    expect($tugas)->not->toBeNull()
        ->and($tugas->sudahTerbit())->toBeFalse();
});

test('guru tidak dapat membuat tugas dengan deadline sebelum tanggal terbit', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.tugas.store'), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Tugas Salah Waktu',
            'tanggal_terbit' => now()->addDay()->format('Y-m-d H:i'),
            'deadline' => now()->format('Y-m-d H:i'),
        ])
        ->assertSessionHasErrors('deadline');

    $this->assertDatabaseMissing('tugases', ['judul' => 'Tugas Salah Waktu']);
});

test('guru tidak dapat membuat tugas tanpa deadline', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.tugas.store'), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Tugas Tanpa Deadline',
        ])
        ->assertSessionHasErrors('deadline');
});

test('guru tidak dapat membuat tugas ke penugasan milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.tugas.store'), [
            'guru_kelas_id' => $guruKelasLain->id,
            'judul' => 'Tugas Ilegal',
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
        ])
        ->assertSessionHasErrors('guru_kelas_id');

    $this->assertDatabaseMissing('tugases', ['judul' => 'Tugas Ilegal']);
});

test('guru dapat membuka data tugas miliknya untuk diedit', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas 1',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.edit', $tugas))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Tugas/Index')
            ->where('editTugas.id', $tugas->id)
            ->where('editTugas.guru_kelas_id', $this->guruKelasA->id)
            ->where('editTugas.judul', 'Tugas 1'));
});

test('guru dapat memperbarui tugas miliknya', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas 1',
    ]);

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.tugas.update', $tugas), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Tugas 1 Revisi',
            'deskripsi' => 'Deskripsi baru',
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tugases', [
        'id' => $tugas->id,
        'judul' => 'Tugas 1 Revisi',
        'deskripsi' => 'Deskripsi baru',
    ]);
});

test('guru dapat membuat tugas dengan berbagai cara pengumpulan', function (): void {
    $data = [
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas Teks',
        'deadline' => now()->addWeek()->format('Y-m-d H:i'),
    ];

    foreach (['file', 'teks', 'keduanya'] as $jenis) {
        $this->actingAs($this->guruUser)
            ->post(route('app.guru.tugas.store'), [...$data, 'jenis_pengumpulan' => $jenis])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tugases', ['judul' => 'Tugas Teks', 'jenis_pengumpulan' => $jenis]);
    }
});

test('guru tidak dapat memakai cara pengumpulan yang tidak dikenal', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.tugas.store'), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Tugas Aneh',
            'jenis_pengumpulan' => 'video',
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
        ])
        ->assertSessionHasErrors('jenis_pengumpulan');

    $this->assertDatabaseMissing('tugases', ['judul' => 'Tugas Aneh']);
});

test('guru dapat mengganti cara pengumpulan tugas dan tetap menyimpan berkas lampiran', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas 1',
        'jenis_pengumpulan' => 'file',
        'file_name' => 'lampiran.pdf',
    ]);
    Storage::disk('public')->put($tugas->file_path, 'isi lama');

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.tugas.update', $tugas), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Tugas 1',
            'jenis_pengumpulan' => 'teks',
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $tugas->refresh();
    $this->assertSame('teks', $tugas->jenis_pengumpulan);
    $this->assertSame('lampiran.pdf', $tugas->file_name);
});

test('guru dapat mengganti berkas tugas dan berkas lama dihapus', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'file_name' => 'lama.pdf',
    ]);
    Storage::disk('public')->put($tugas->file_path, 'isi lama');

    $file = UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf');

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.tugas.update', $tugas), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Tugas 1',
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
            'file' => $file,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $tugas->refresh();
    $this->assertSame('baru.pdf', $tugas->file_name);
    Storage::disk('public')->assertMissing('tugas/lama.pdf');
    Storage::disk('public')->assertExists($tugas->file_path);
});

test('guru tidak dapat mengedit tugas milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $tugas = Tugas::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Judul Asli',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.edit', $tugas))
        ->assertNotFound();

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.tugas.update', $tugas), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Judul Baru',
            'deadline' => now()->addWeek()->format('Y-m-d H:i'),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('tugases', [
        'id' => $tugas->id,
        'judul' => 'Judul Asli',
    ]);
});

test('guru dapat membuka halaman pengumpulan dengan status tiap siswa', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'deadline' => now()->subHour(),
    ]);
    TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_path' => 'tugas-kumpul/jawaban.pdf',
        'file_name' => 'jawaban.pdf',
        'file_size' => 100,
        'mime_type' => 'application/pdf',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.pengumpulan', $tugas))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Tugas/Pengumpulan')
            ->where('tugas.judul', $tugas->judul)
            ->where('tugas.jumlah_siswa', 1)
            ->where('siswas.0.nama', $this->siswaA->nama_lengkap)
            ->where('siswas.0.terlambat', true)
            ->where('siswas.0.file_name', 'jawaban.pdf'));
});

test('guru tidak dapat membuka pengumpulan tugas milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $tugas = Tugas::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.pengumpulan', $tugas))
        ->assertNotFound();
});

test('guru dapat mengunduh berkas pengumpulan siswa', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);
    $pengumpulan = TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_path' => 'tugas-kumpul/jawaban.pdf',
        'file_name' => 'jawaban.pdf',
        'file_size' => 100,
        'mime_type' => 'application/pdf',
        'submitted_at' => now(),
    ]);
    Storage::disk('public')->put('tugas-kumpul/jawaban.pdf', 'isi jawaban');

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.pengumpulan.unduh', [$tugas, $pengumpulan]))
        ->assertOk()
        ->assertDownload('jawaban.pdf');
});

test('guru tidak dapat mengunduh pengumpulan teks yang tidak punya berkas', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'jenis_pengumpulan' => 'teks',
    ]);
    $pengumpulan = TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_path' => null,
        'file_name' => null,
        'file_size' => 0,
        'jawaban_teks' => 'Jawaban esai saya',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.pengumpulan.unduh', [$tugas, $pengumpulan]))
        ->assertNotFound();
});

test('guru dapat menghapus tugas beserta berkas dan pengumpulannya', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);
    Storage::disk('public')->put($tugas->file_path, 'isi berkas');
    $pengumpulan = TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_path' => 'tugas-kumpul/jawaban.pdf',
        'file_name' => 'jawaban.pdf',
        'file_size' => 100,
        'mime_type' => 'application/pdf',
        'submitted_at' => now(),
    ]);
    Storage::disk('public')->put('tugas-kumpul/jawaban.pdf', 'isi jawaban');

    $this->actingAs($this->guruUser)
        ->delete(route('app.guru.tugas.destroy', $tugas))
        ->assertRedirect();

    Storage::disk('public')->assertMissing($tugas->file_path);
    Storage::disk('public')->assertMissing('tugas-kumpul/jawaban.pdf');
    $this->assertDatabaseMissing('tugases', ['id' => $tugas->id]);
    $this->assertDatabaseMissing('tugas_pengumpulans', ['id' => $pengumpulan->id]);
});

test('guru tidak dapat menghapus tugas milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $tugas = Tugas::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);

    $this->actingAs($this->guruUser)
        ->delete(route('app.guru.tugas.destroy', $tugas))
        ->assertRedirect();

    $this->assertDatabaseHas('tugases', ['id' => $tugas->id]);
});

test('guru dapat memfilter tugas berdasarkan penugasan dan kata kunci', function (): void {
    $guruKelasB = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas Aljabar',
    ]);
    Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $guruKelasB->id,
        'judul' => 'Tugas Geometri',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.index', ['guru_kelas_id' => $guruKelasB->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Tugas/Index')
            ->has('tugases.data', 1)
            ->where('tugases.data.0.judul', 'Tugas Geometri'));

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.tugas.index', ['q' => 'Aljabar']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Tugas/Index')
            ->has('tugases.data', 1)
            ->where('tugases.data.0.judul', 'Tugas Aljabar'));
});

test('halaman guru tugas memblokir akses siswa', function (): void {
    $this->actingAs($this->siswaAUser)
        ->get(route('app.guru.tugas.index'))
        ->assertForbidden();
});
