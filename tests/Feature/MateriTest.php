<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Matpel;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
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

test('guru dapat membuka halaman materi', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Materi/Index')
            ->has('penugasan', 1)
            ->where('penugasan.0.label', 'X-RPL-1 — Matematika'));
});

test('guru dapat mengunggah materi untuk penugasannya', function (): void {
    $file = UploadedFile::fake()->create('bab-1.pdf', 100, 'application/pdf');

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.materi.store'), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Bab 1: Bilangan',
            'deskripsi' => 'Materi pengantar',
            'file' => $file,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('materis', [
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Bab 1: Bilangan',
        'file_name' => 'bab-1.pdf',
    ]);

    Storage::disk('public')->assertExists(Materi::first()->file_path);
});

test('guru dapat memfilter materinya berdasarkan penugasan dan kata kunci', function (): void {
    $guruKelasB = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Bab 1: Bilangan',
    ]);
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $guruKelasB->id,
        'judul' => 'Bab 2: Aljabar',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.index', ['guru_kelas_id' => $guruKelasB->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Materi/Index')
            ->has('materis.data', 1)
            ->where('materis.data.0.judul', 'Bab 2: Aljabar'));

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.index', ['q' => 'Aljabar']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Materi/Index')
            ->has('materis.data', 1)
            ->where('materis.data.0.judul', 'Bab 2: Aljabar'));
});

test('guru dapat mengunggah materi tanpa berkas dengan konten lengkap', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.materi.store'), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Materi Teks Saja',
            'deskripsi' => 'Ringkasan singkat.',
            'konten' => '<h2 id="judul-1">Pendahuluan</h2><p>Rumus: </p>',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $materi = Materi::query()->where('judul', 'Materi Teks Saja')->first();

    expect($materi)->not->toBeNull()
        ->and($materi->file_path)->toBeNull()
        ->and($materi->file_name)->toBeNull()
        ->and($materi->konten)->toContain('<h2');

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.show', $materi))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Materi/Detail')
            ->where('materi.has_konten', true)
            ->where('materi.file_name', null)
            ->missing('konten'));

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.show', $materi), [
            'X-Inertia-Partial-Component' => 'siswa/Materi/Detail',
            'X-Inertia-Partial-Data' => 'konten',
        ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Materi/Detail')
            ->where('konten', $materi->konten));
});

test('guru tidak dapat mengunggah materi ke penugasan milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.materi.store'), [
            'guru_kelas_id' => $guruKelasLain->id,
            'judul' => 'Materi Ilegal',
            'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors('guru_kelas_id');

    $this->assertDatabaseMissing('materis', ['judul' => 'Materi Ilegal']);
});

test('guru dapat membuka katalog materi semua guru', function (): void {
    $guruLain = Guru::factory()->create();
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    Materi::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $guruKelasLain->id,
        'judul' => 'Materi Guru Lain',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.katalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Materi/Index')
            ->has('katalog.data', 1)
            ->where('katalog.data.0.judul', 'Materi Guru Lain')
            ->has('katalogFilters.tahunAjaran')
            ->has('katalogFilters.kelas', 1)
            ->where('katalogFilters.kelas.0.label', 'X-RPL-1')
            ->where('katalogFilters.kelas.0.taIds.0', $this->tahunAjaran->id)
            ->has('katalogFilters.matpel', 1)
            ->where('katalogFilters.matpel.0.label', 'Matematika'));
});

test('katalog materi dapat difilter tahun ajaran, kelas, dan matpel', function (): void {
    $tahunLain = TahunAjaran::factory()->create(['active' => false]);
    $guruLain = Guru::factory()->create();
    $guruKelasTahunLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $tahunLain->id,
        'aktif' => true,
    ]);
    Materi::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $guruKelasTahunLain->id,
        'judul' => 'Materi Tahun Lalu',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.katalog', ['tahun_ajaran_id' => $tahunLain->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Materi/Index')
            ->has('katalog.data', 1)
            ->where('katalog.data.0.judul', 'Materi Tahun Lalu'));

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.katalog', ['kelas_id' => $this->kelasB->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Materi/Index')
            ->has('katalog.data', 1)
            ->where('katalog.data.0.judul', 'Materi Tahun Lalu'));

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.katalog', ['matpel_id' => $this->matpel->id, 'q' => 'Tahun Lalu']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Materi/Index')
            ->has('katalog.data', 1));
});

test('guru dapat menyalin materi dari katalog beserta berkasnya', function (): void {
    $guruLain = Guru::factory()->create();
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => $guruLain->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    $sumber = Materi::create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $guruKelasLain->id,
        'judul' => 'Materi Sumber',
        'deskripsi' => 'Ringkasan sumber',
        'konten' => '<h2>Isi</h2>',
        'file_path' => 'materi/sumber.pdf',
        'file_name' => 'sumber.pdf',
        'file_size' => 1234,
        'mime_type' => 'application/pdf',
    ]);
    Storage::disk('public')->put('materi/sumber.pdf', 'fake pdf content');

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.materi.salin'), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'materi_id' => $sumber->id,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $hasil = Materi::query()->where('judul', 'Materi Sumber')->where('guru_id', $this->guru->id)->first();

    expect($hasil)->not->toBeNull()
        ->and($hasil->guru_kelas_id)->toBe($this->guruKelasA->id)
        ->and($hasil->konten)->toBe($sumber->konten)
        ->and($hasil->file_name)->toBe('sumber.pdf')
        ->and($hasil->file_path)->not->toBe($sumber->file_path);

    Storage::disk('public')->assertExists($hasil->file_path);
});

test('guru tidak dapat menyalin materi ke penugasan milik guru lain', function (): void {
    $sumber = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Materi Sumber',
    ]);
    $guruKelasLain = GuruKelas::factory()->create([
        'guru_id' => Guru::factory()->create()->id,
        'kelas_id' => $this->kelasB->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.materi.salin'), [
            'guru_kelas_id' => $guruKelasLain->id,
            'materi_id' => $sumber->id,
        ])
        ->assertSessionHasErrors('guru_kelas_id');
});

test('siswa menerima materi yang dikirim ke kelasnya', function (): void {
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Bab 1: Bilangan',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Materi/Index')
            ->where('kelas', 'X-RPL-1')
            ->has('materis.data', 1)
            ->where('materis.data.0.judul', 'Bab 1: Bilangan'));
});

test('siswa tidak menerima materi dari kelas lain', function (): void {
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Bab 1: Bilangan',
    ]);

    $this->actingAs($this->siswaBUser)
        ->get(route('app.siswa.materi.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Materi/Index')
            ->where('kelas', 'X-RPL-2')
            ->has('materis.data', 0));
});

test('guru dapat mengunduh materi miliknya', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);
    Storage::disk('public')->put($materi->file_path, 'isi berkas');

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.unduh', $materi))
        ->assertOk()
        ->assertDownload($materi->file_name);
});

test('siswa hanya dapat mengunduh materi kelasnya sendiri', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);
    Storage::disk('public')->put($materi->file_path, 'isi berkas');

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.unduh', $materi))
        ->assertOk()
        ->assertDownload($materi->file_name);

    $this->actingAs($this->siswaBUser)
        ->get(route('app.siswa.materi.unduh', $materi))
        ->assertNotFound();
});

test('siswa dapat membuka materi kelasnya secara inline', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);
    Storage::disk('public')->put($materi->file_path, 'isi berkas');

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.lihat', $materi))
        ->assertOk();

    $this->actingAs($this->siswaBUser)
        ->get(route('app.siswa.materi.lihat', $materi))
        ->assertNotFound();
});

test('siswa dapat membuka halaman detail materi kelasnya', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Bab 1: Bilangan',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.show', $materi))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Materi/Detail')
            ->where('materi.judul', 'Bab 1: Bilangan')
            ->where('materi.kelas', 'X-RPL-1'));
});

test('siswa tidak dapat membuka detail materi dari kelas lain', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);

    $this->actingAs($this->siswaBUser)
        ->get(route('app.siswa.materi.show', $materi))
        ->assertNotFound();
});

test('siswa dapat memfilter materi berdasarkan mata pelajaran', function (): void {
    $matpelB = Matpel::factory()->create(['name' => 'Bahasa Indonesia']);
    $guruKelasB = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $matpelB->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Matematika: Aljabar',
    ]);
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $guruKelasB->id,
        'judul' => 'Bahasa: Puisi',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.index', ['matpel' => $matpelB->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Materi/Index')
            ->has('materis.data', 1)
            ->where('materis.data.0.judul', 'Bahasa: Puisi')
            ->where('filters.matpel', $matpelB->id));
});

test('siswa dapat mencari materi berdasarkan judul', function (): void {
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Matematika: Aljabar',
    ]);
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Matematika: Geometri',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.index', ['q' => 'Aljabar']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Materi/Index')
            ->has('materis.data', 1)
            ->where('materis.data.0.judul', 'Matematika: Aljabar'));
});

test('daftar filter mata pelajaran memuat semua matpel kelas siswa meski belum ada materi', function (): void {
    $matpelB = Matpel::factory()->create(['name' => 'Bahasa Indonesia']);
    GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelasA->id,
        'matpel_id' => $matpelB->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Matematika: Aljabar',
    ]);

    $this->actingAs($this->siswaAUser)
        ->get(route('app.siswa.materi.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('siswa/Materi/Index')
            ->has('matpelList', 2)
            ->where('matpelList.0.label', 'Matematika')
            ->where('matpelList.1.label', 'Bahasa Indonesia'));
});

test('guru dapat menghapus materi miliknya beserta berkasnya', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);
    Storage::disk('public')->put($materi->file_path, 'isi berkas');

    $this->actingAs($this->guruUser)
        ->delete(route('app.guru.materi.destroy', $materi))
        ->assertRedirect();

    Storage::disk('public')->assertMissing($materi->file_path);
    $this->assertDatabaseMissing('materis', ['id' => $materi->id]);
});

test('guru tidak dapat menghapus materi milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $materi = Materi::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $this->guruKelasA->id,
    ]);

    $this->actingAs($this->guruUser)
        ->delete(route('app.guru.materi.destroy', $materi))
        ->assertRedirect();

    $this->assertDatabaseHas('materis', ['id' => $materi->id]);
});

test('guru dapat membuka data materi miliknya untuk diedit', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Bab 1: Bilangan',
        'konten' => '<p>Isi lama</p>',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.edit', $materi))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/Materi/Index')
            ->where('editMateri.id', $materi->id)
            ->where('editMateri.guru_kelas_id', $this->guruKelasA->id)
            ->where('editMateri.judul', 'Bab 1: Bilangan')
            ->where('editMateri.konten', '<p>Isi lama</p>'));
});

test('guru dapat memperbarui judul, deskripsi, dan konten materinya', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Bab 1: Bilangan',
    ]);

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.materi.update', $materi), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Bab 1: Bilangan Bulat',
            'deskripsi' => 'Deskripsi baru',
            'konten' => '<h2 id="judul-1">Pengantar</h2>',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('materis', [
        'id' => $materi->id,
        'judul' => 'Bab 1: Bilangan Bulat',
        'deskripsi' => 'Deskripsi baru',
        'konten' => '<h2 id="judul-1">Pengantar</h2>',
    ]);
});

test('guru dapat mengganti berkas materi dan berkas lama dihapus', function (): void {
    $materi = Materi::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'file_name' => 'lama.pdf',
    ]);
    Storage::disk('public')->put($materi->file_path, 'isi lama');

    $file = UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf');

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.materi.update', $materi), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Bab 1: Bilangan',
            'file' => $file,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $materi->refresh();
    $this->assertSame('baru.pdf', $materi->file_name);
    Storage::disk('public')->assertMissing('materi/lama.pdf');
    Storage::disk('public')->assertExists($materi->file_path);
});

test('guru tidak dapat mengedit materi milik guru lain', function (): void {
    $guruLain = Guru::factory()->create();
    $materi = Materi::factory()->create([
        'guru_id' => $guruLain->id,
        'guru_kelas_id' => $this->guruKelasA->id,
        'judul' => 'Judul Asli',
    ]);

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.materi.edit', $materi))
        ->assertNotFound();

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.materi.update', $materi), [
            'guru_kelas_id' => $this->guruKelasA->id,
            'judul' => 'Judul Baru',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('materis', [
        'id' => $materi->id,
        'judul' => 'Judul Asli',
    ]);
});

test('halaman guru materi memblokir akses siswa', function (): void {
    $this->actingAs($this->siswaAUser)
        ->get(route('app.guru.materi.index'))
        ->assertForbidden();
});

test('halaman siswa materi memblokir akses guru', function (): void {
    $this->actingAs($this->guruUser)
        ->get(route('app.siswa.materi.index'))
        ->assertForbidden();
});
