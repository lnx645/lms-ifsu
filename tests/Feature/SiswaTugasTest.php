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

    $this->siswaB = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswaB->nisn,
        'kelas_id' => $this->kelasB->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $this->siswaBUser = User::factory()->create(['nisn' => $this->siswaB->nisn]);
});

test('siswa melihat tugas terbit di kelasnya beserta status', function (): void {
    Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas 1',
        'deadline' => now()->addWeek(),
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.tugas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Tugas/Index')
            ->has('tugases', 1)
            ->where('tugases.0.judul', 'Tugas 1')
            ->where('tugases.0.kelas', 'X-RPL-1')
            ->where('tugases.0.matpel', 'Matematika')
            ->where('tugases.0.status', 'belum'));
});

test('siswa tidak melihat tugas yang belum terbit', function (): void {
    Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas Masa Depan',
        'tanggal_terbit' => now()->addDay(),
        'deadline' => now()->addWeek(),
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.tugas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Tugas/Index')
            ->has('tugases', 0));
});

test('siswa tidak melihat tugas dari kelas lain', function (): void {
    Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas Kelas A',
    ]);

    $this->actingAs($this->siswaBUser)
        ->get(route('app.siswa.tugas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Tugas/Index')
            ->has('tugases', 0));
});

test('siswa melihat status tugas yang sudah dikumpulkan dan yang ditutup', function (): void {
    $tugasTerlambat = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas Dikumpulkan',
        'deadline' => now()->subDay(),
    ]);
    TugasPengumpulan::create([
        'tugas_id' => $tugasTerlambat->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_path' => 'tugas-kumpul/jawaban.pdf',
        'file_name' => 'jawaban.pdf',
        'file_size' => 100,
        'mime_type' => 'application/pdf',
        'submitted_at' => now()->subHour(),
    ]);
    Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas Ditutup',
        'deadline' => now()->subDay(),
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.tugas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Tugas/Index')
            ->has('tugases', 2)
            ->where('tugases.0.status', 'terlambat')
            ->where('tugases.1.status', 'tutup'));
});

test('siswa dapat membuka detail tugas kelasnya', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Tugas 1',
        'deskripsi' => 'Kerjakan dengan teliti.',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.tugas.show', $tugas))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Tugas/Detail')
            ->where('tugas.judul', 'Tugas 1')
            ->where('tugas.kelas', 'X-RPL-1')
            ->where('tugas.status', 'belum')
            ->where('pengumpulan', null));
});

test('siswa tidak dapat membuka detail tugas dari kelas lain atau yang belum terbit', function (): void {
    $tugasKelasA = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);
    $tugasBelumTerbit = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'tanggal_terbit' => now()->addDay(),
    ]);

    $this->actingAs($this->siswaBUser)
        ->get(route('app.siswa.tugas.show', $tugasKelasA))
        ->assertNotFound();

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.tugas.show', $tugasBelumTerbit))
        ->assertNotFound();
});

test('siswa dapat mengumpulkan tugas dengan berkas jawaban', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'deadline' => now()->addWeek(),
    ]);

    $file = UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf');

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tugas_pengumpulans', [
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_name' => 'jawaban.pdf',
    ]);

    Storage::disk('public')->assertExists(
        TugasPengumpulan::query()->where('tugas_id', $tugas->id)->first()->file_path,
    );
});

test('siswa dapat mengganti jawaban namun waktu pengumpulan pertama tetap', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'deadline' => now()->addWeek(),
    ]);
    $pengumpulan = TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_path' => 'tugas-kumpul/lama.pdf',
        'file_name' => 'lama.pdf',
        'file_size' => 100,
        'mime_type' => 'application/pdf',
        'submitted_at' => now()->subDay(),
    ]);
    Storage::disk('public')->put('tugas-kumpul/lama.pdf', 'isi lama');

    $file = UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf');

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $pengumpulan->refresh();
    $this->assertSame('baru.pdf', $pengumpulan->file_name);
    expect($pengumpulan->submitted_at->toDateTimeString())->toBe(now()->subDay()->toDateTimeString());

    Storage::disk('public')->assertMissing('tugas-kumpul/lama.pdf');
    Storage::disk('public')->assertExists($pengumpulan->file_path);
});

test('siswa tidak dapat mengumpulkan tugas setelah deadline', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'deadline' => now()->subHour(),
    ]);

    $file = UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf');

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseMissing('tugas_pengumpulans', ['tugas_id' => $tugas->id]);
});

test('siswa tidak dapat mengumpulkan tugas kelas lain atau yang belum terbit', function (): void {
    $tugasKelasA = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'deadline' => now()->addWeek(),
    ]);
    $tugasBelumTerbit = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'tanggal_terbit' => now()->addDay(),
        'deadline' => now()->addWeek(),
    ]);

    $file = UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf');

    $this->actingAs($this->siswaBUser)
        ->post(route('app.siswa.tugas.kumpul', $tugasKelasA), ['file' => $file])
        ->assertNotFound();

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugasBelumTerbit), ['file' => $file])
        ->assertNotFound();
});

test('siswa tidak dapat mengumpulkan tugas tanpa berkas', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'deadline' => now()->addWeek(),
    ]);

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas))
        ->assertSessionHasErrors('file');
});

test('siswa dapat mengumpulkan tugas dengan jawaban teks', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'jenis_pengumpulan' => 'teks',
        'deadline' => now()->addWeek(),
    ]);

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), ['jawaban_teks' => 'Jawaban esai saya'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tugas_pengumpulans', [
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'jawaban_teks' => 'Jawaban esai saya',
        'file_name' => null,
    ]);
});

test('siswa tidak dapat mengumpulkan tugas teks tanpa jawaban atau dengan berkas', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'jenis_pengumpulan' => 'teks',
        'deadline' => now()->addWeek(),
    ]);

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), ['jawaban_teks' => ''])
        ->assertSessionHasErrors('jawaban_teks');

    $file = UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf');

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), [
            'jawaban_teks' => 'Jawaban esai saya',
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});

test('siswa tidak dapat mengumpulkan tugas file dengan jawaban teks', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'jenis_pengumpulan' => 'file',
        'deadline' => now()->addWeek(),
    ]);

    $file = UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf');

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), [
            'file' => $file,
            'jawaban_teks' => 'Jawaban teks',
        ])
        ->assertSessionHasErrors('jawaban_teks');
});

test('siswa dapat mengumpulkan tugas dengan berkas dan teks sekaligus', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'jenis_pengumpulan' => 'keduanya',
        'deadline' => now()->addWeek(),
    ]);

    $file = UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf');

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), [
            'file' => $file,
            'jawaban_teks' => 'Jawaban esai saya',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tugas_pengumpulans', [
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'jawaban_teks' => 'Jawaban esai saya',
    ]);
});

test('siswa dapat mengganti jawaban teks tanpa menghapus berkas lama', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'jenis_pengumpulan' => 'keduanya',
        'deadline' => now()->addWeek(),
    ]);
    $pengumpulan = TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_path' => 'tugas-kumpul/lama.pdf',
        'file_name' => 'lama.pdf',
        'file_size' => 100,
        'mime_type' => 'application/pdf',
        'jawaban_teks' => 'Versi lama',
        'submitted_at' => now()->subDay(),
    ]);
    Storage::disk('public')->put('tugas-kumpul/lama.pdf', 'isi lama');

    $this->actingAs($this->siswaAUser)
        ->post(route('app.siswa.tugas.kumpul', $tugas), ['jawaban_teks' => 'Versi baru'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $pengumpulan->refresh();
    $this->assertSame('Versi baru', $pengumpulan->jawaban_teks);
    $this->assertSame('lama.pdf', $pengumpulan->file_name);
    Storage::disk('public')->assertExists('tugas-kumpul/lama.pdf');
});

test('detail tugas menampilkan cara pengumpulan dan jawaban teks siswa', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'jenis_pengumpulan' => 'keduanya',
        'deadline' => now()->addWeek(),
    ]);
    $pengumpulan = TugasPengumpulan::create([
        'tugas_id' => $tugas->id,
        'siswa_nisn' => $this->siswaA->nisn,
        'file_path' => 'tugas-kumpul/jawaban.pdf',
        'file_name' => 'jawaban.pdf',
        'file_size' => 100,
        'mime_type' => 'application/pdf',
        'jawaban_teks' => 'Jawaban esai saya',
        'submitted_at' => now()->subHour(),
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.tugas.show', $tugas))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Tugas/Detail')
            ->where('tugas.jenis_pengumpulan', 'keduanya')
            ->where('pengumpulan.jawaban_teks', 'Jawaban esai saya'));
});

test('siswa dapat mengunduh berkas tugas kelasnya', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);
    Storage::disk('public')->put($tugas->file_path, 'isi berkas');

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.tugas.unduh', $tugas))
        ->assertOk()
        ->assertDownload($tugas->file_name);

    $this->actingAs($this->siswaBUser)
        ->get(route('app.siswa.tugas.unduh', $tugas))
        ->assertNotFound();
});

test('siswa tidak dapat mengunduh berkas tugas yang belum terbit', function (): void {
    $tugas = Tugas::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'tanggal_terbit' => now()->addDay(),
    ]);
    Storage::disk('public')->put($tugas->file_path, 'isi berkas');

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.tugas.unduh', $tugas))
        ->assertNotFound();
});

test('halaman siswa tugas memblokir akses guru', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.siswa.tugas.index'))
        ->assertForbidden();
});
