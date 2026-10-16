<?php

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function buatAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function buatHierarkiKelas(string $tingkat, string $jurusanKode, string $rombel = '1'): Kelas
{
    $jurusan = Jurusan::firstOrCreate(
        ['kode' => $jurusanKode],
        ['name' => "Jurusan {$jurusanKode}"],
    );

    $root = Kelas::factory()->create([
        'nama' => $tingkat,
        'tingkat' => $tingkat,
        'parent_id' => null,
        'jurusan_id' => null,
        'guru_id' => null,
    ]);

    $nodeJurusan = Kelas::factory()->create([
        'nama' => "{$tingkat}-{$jurusanKode}",
        'tingkat' => null,
        'parent_id' => $root->id,
        'jurusan_id' => $jurusan->id,
        'guru_id' => null,
    ]);

    return Kelas::factory()->create([
        'nama' => "{$tingkat}-{$jurusanKode}-{$rombel}",
        'tingkat' => null,
        'parent_id' => $nodeJurusan->id,
        'jurusan_id' => $jurusan->id,
        'guru_id' => Guru::factory()->create()->id,
    ]);
}

function daftarkanSiswa(Siswa $siswa, Kelas $kelas, TahunAjaran $tahunAjaran): SiswaKelas
{
    return SiswaKelas::create([
        'siswa_nisn' => $siswa->nisn,
        'kelas_id' => $kelas->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
        'active' => true,
        'pertama_masuk' => true,
    ]);
}

test('halaman index menampilkan daftar tahun ajaran', function (): void {
    $admin = buatAdmin();
    TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.naik-kelas.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/NaikKelas/Index')
            ->where('preview', null)
            ->has('tahun_ajaran', 1));
});

test('preview menghitung pemetaan naik kelas', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXI = buatHierarkiKelas('XI', 'RPL');
    $kelasXII = buatHierarkiKelas('XII', 'TKJ');

    $siswa1 = Siswa::factory()->create();
    $siswa2 = Siswa::factory()->create();

    daftarkanSiswa($siswa1, $kelasX, $sumber);
    daftarkanSiswa($siswa2, $kelasXII, $sumber);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.preview'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
        ])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/NaikKelas/Index')
            ->where('preview.sumber.id', $sumber->id)
            ->where('preview.target.id', $target->id)
            ->has('preview.kelas', 2)
            ->where('preview.ringkasan.naik', 1)
            ->where('preview.ringkasan.lulus', 1));
});

test('preview menyediakan daftar kelas tujuan tingkat berikutnya', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXI1 = buatHierarkiKelas('XI', 'RPL', '1');
    $kelasXI2 = buatHierarkiKelas('XI', 'TKJ', '2');

    $siswa = Siswa::factory()->create();
    daftarkanSiswa($siswa, $kelasX, $sumber);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.preview'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
        ])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/NaikKelas/Index')
            ->where('preview.kelas.0.kelas_target_id', $kelasXI1->id)
            ->has('preview.kelas_tujuan.X', 2)
            ->where('preview.kelas_tujuan.X.0.value', $kelasXI1->id)
            ->where('preview.kelas_tujuan.X.1.value', $kelasXI2->id));
});

test('preview menolak sumber sama dengan target', function (): void {
    $admin = buatAdmin();
    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.preview'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $sumber->id,
        ])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/NaikKelas/Index')
            ->where('preview', null));
});

test('execute memindahkan siswa naik, tinggal, dan lulus', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXI = buatHierarkiKelas('XI', 'RPL');
    $kelasXII = buatHierarkiKelas('XII', 'TKJ');

    $siswaNaik = Siswa::factory()->create();
    $siswaTinggal = Siswa::factory()->create();
    $siswaLulus = Siswa::factory()->create();

    daftarkanSiswa($siswaNaik, $kelasX, $sumber);
    daftarkanSiswa($siswaTinggal, $kelasX, $sumber);
    daftarkanSiswa($siswaLulus, $kelasXII, $sumber);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.execute'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
            'pilihan' => [
                ['nisn' => $siswaNaik->nisn, 'status' => 'naik'],
                ['nisn' => $siswaTinggal->nisn, 'status' => 'tinggal'],
                ['nisn' => $siswaLulus->nisn, 'status' => 'lulus'],
            ],
        ])
        ->assertRedirect(route('admin.naik-kelas.index'));

    $this->assertDatabaseHas('siswa_kelas', [
        'siswa_nisn' => $siswaNaik->nisn,
        'kelas_id' => $kelasXI->id,
        'tahun_ajaran_id' => $target->id,
        'active' => true,
    ]);

    $this->assertDatabaseHas('siswa_kelas', [
        'siswa_nisn' => $siswaTinggal->nisn,
        'kelas_id' => $kelasX->id,
        'tahun_ajaran_id' => $target->id,
        'active' => true,
    ]);

    $this->assertDatabaseHas('siswa', [
        'nisn' => $siswaLulus->nisn,
        'status' => 'lulus',
    ]);

    expect(SiswaKelas::query()
        ->where('tahun_ajaran_id', $sumber->id)
        ->where('active', true)
        ->count())->toBe(0);
});

test('execute idempoten: menjalankan dua kali tidak menggandakan', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXI = buatHierarkiKelas('XI', 'RPL');

    $siswa = Siswa::factory()->create();
    daftarkanSiswa($siswa, $kelasX, $sumber);

    $payload = [
        'tahun_ajaran_sumber' => $sumber->id,
        'tahun_ajaran_target' => $target->id,
        'pilihan' => [
            ['nisn' => $siswa->nisn, 'status' => 'naik'],
        ],
    ];

    $this->actingAs($admin)->post(route('admin.naik-kelas.execute'), $payload);
    $this->actingAs($admin)->post(route('admin.naik-kelas.execute'), $payload);

    $this->assertDatabaseCount('siswa_kelas', 2);

    $this->assertDatabaseHas('siswa_kelas', [
        'siswa_nisn' => $siswa->nisn,
        'kelas_id' => $kelasXI->id,
        'tahun_ajaran_id' => $target->id,
        'active' => true,
    ]);
});

test('execute validasi menolak status tidak valid', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.execute'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
            'pilihan' => [
                ['nisn' => '12345', 'status' => 'rahasia'],
            ],
        ])
        ->assertSessionHasErrors('pilihan.0.status');
});

test('execute memindahkan siswa naik ke kelas tujuan yang dipilih admin', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXI1 = buatHierarkiKelas('XI', 'RPL', '1');
    $kelasXI2 = buatHierarkiKelas('XI', 'RPL', '2');

    $siswa = Siswa::factory()->create();
    daftarkanSiswa($siswa, $kelasX, $sumber);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.execute'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
            'pilihan' => [
                ['nisn' => $siswa->nisn, 'status' => 'naik', 'kelas_target' => $kelasXI2->id],
            ],
        ])
        ->assertRedirect(route('admin.naik-kelas.index'));

    $this->assertDatabaseHas('siswa_kelas', [
        'siswa_nisn' => $siswa->nisn,
        'kelas_id' => $kelasXI2->id,
        'tahun_ajaran_id' => $target->id,
        'active' => true,
    ]);

    expect(SiswaKelas::query()
        ->where('siswa_nisn', $siswa->nisn)
        ->where('kelas_id', $kelasXI1->id)
        ->where('tahun_ajaran_id', $target->id)
        ->exists())->toBeFalse();
});

test('execute tanpa kelas_target memakai target otomatis', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXI = buatHierarkiKelas('XI', 'RPL');

    $siswa = Siswa::factory()->create();
    daftarkanSiswa($siswa, $kelasX, $sumber);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.execute'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
            'pilihan' => [
                ['nisn' => $siswa->nisn, 'status' => 'naik', 'kelas_target' => null],
            ],
        ])
        ->assertRedirect(route('admin.naik-kelas.index'));

    $this->assertDatabaseHas('siswa_kelas', [
        'siswa_nisn' => $siswa->nisn,
        'kelas_id' => $kelasXI->id,
        'tahun_ajaran_id' => $target->id,
        'active' => true,
    ]);
});

test('execute menolak kelas tujuan di luar tingkat berikutnya', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXII = buatHierarkiKelas('XII', 'RPL');

    $siswa = Siswa::factory()->create();
    daftarkanSiswa($siswa, $kelasX, $sumber);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.execute'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
            'pilihan' => [
                ['nisn' => $siswa->nisn, 'status' => 'naik', 'kelas_target' => $kelasXII->id],
            ],
        ])
        ->assertSessionHasErrors('pilihan');

    $this->assertDatabaseMissing('siswa_kelas', [
        'siswa_nisn' => $siswa->nisn,
        'kelas_id' => $kelasXII->id,
        'tahun_ajaran_id' => $target->id,
    ]);
});

test('execute mengabaikan kelas_target saat status tinggal', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXI = buatHierarkiKelas('XI', 'RPL');

    $siswa = Siswa::factory()->create();
    daftarkanSiswa($siswa, $kelasX, $sumber);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.execute'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
            'pilihan' => [
                ['nisn' => $siswa->nisn, 'status' => 'tinggal', 'kelas_target' => $kelasXI->id],
            ],
        ])
        ->assertRedirect(route('admin.naik-kelas.index'));

    $this->assertDatabaseHas('siswa_kelas', [
        'siswa_nisn' => $siswa->nisn,
        'kelas_id' => $kelasX->id,
        'tahun_ajaran_id' => $target->id,
        'active' => true,
    ]);
});

test('promoteTarget memetakan X-RPL-1 ke XI-RPL-1', function (): void {
    $kelasX = buatHierarkiKelas('X', 'RPL');
    buatHierarkiKelas('XI', 'RPL');

    expect($kelasX->promoteTarget())->not->toBeNull();
    expect($kelasX->promoteTarget()->nama)->toBe('XI-RPL-1');
});

test('promoteTarget menormalkan spasi ke dash saat mencari target', function (): void {
    $jurusan = Jurusan::firstOrCreate(
        ['kode' => 'RPL'],
        ['name' => 'Jurusan RPL'],
    );

    $rootX = Kelas::factory()->create([
        'nama' => 'X',
        'tingkat' => 'X',
        'parent_id' => null,
        'jurusan_id' => null,
        'guru_id' => null,
    ]);

    $kelasAsal = Kelas::factory()->create([
        'nama' => 'X RPL 1',
        'tingkat' => null,
        'parent_id' => $rootX->id,
        'jurusan_id' => $jurusan->id,
        'guru_id' => Guru::factory()->create()->id,
    ]);

    $rootXI = Kelas::factory()->create([
        'nama' => 'XI',
        'tingkat' => 'XI',
        'parent_id' => null,
        'jurusan_id' => null,
        'guru_id' => null,
    ]);

    Kelas::factory()->create([
        'nama' => 'XI-RPL-1',
        'tingkat' => null,
        'parent_id' => $rootXI->id,
        'jurusan_id' => $jurusan->id,
        'guru_id' => Guru::factory()->create()->id,
    ]);

    expect($kelasAsal->promoteTarget())->not->toBeNull();
    expect($kelasAsal->promoteTarget()->nama)->toBe('XI-RPL-1');
});

test('tingkatBerikutnya mengembalikan null untuk XII', function (): void {
    expect(Kelas::tingkatBerikutnya('XII'))->toBeNull();
    expect(Kelas::tingkatBerikutnya('X'))->toBe('XI');
});

test('tingkatSekarang memakai nama saat root tidak punya tingkat (data riil)', function (): void {
    $jurusan = Jurusan::firstOrCreate(
        ['kode' => 'RPL'],
        ['name' => 'Jurusan RPL'],
    );

    $rootXI = Kelas::factory()->create([
        'nama' => 'XI',
        'tingkat' => null,
        'parent_id' => null,
        'jurusan_id' => null,
        'guru_id' => null,
    ]);

    $nodeJurusan = Kelas::factory()->create([
        'nama' => 'XI-RPL',
        'tingkat' => null,
        'parent_id' => $rootXI->id,
        'jurusan_id' => $jurusan->id,
        'guru_id' => null,
    ]);

    $kelasXI = Kelas::factory()->create([
        'nama' => 'XI-RPL-1',
        'tingkat' => null,
        'parent_id' => $nodeJurusan->id,
        'jurusan_id' => $jurusan->id,
        'guru_id' => Guru::factory()->create()->id,
    ]);

    expect($kelasXI->tingkatSekarang())->toBe('XI');
    expect(Kelas::tingkatBerikutnya($kelasXI->tingkatSekarang()))->toBe('XII');
});

test('preview kelas tujuan tidak menampilkan kelas asal saat root XI tingkat null (data riil)', function (): void {
    $admin = buatAdmin();

    $sumber = TahunAjaran::factory()->create(['name' => '2025/2026', 'active' => true]);
    $target = TahunAjaran::factory()->create(['name' => '2026/2027', 'active' => false]);

    $kelasX = buatHierarkiKelas('X', 'RPL');
    $kelasXI = buatHierarkiKelas('XI', 'RPL');
    Kelas::query()->where('nama', 'XI')->whereNull('parent_id')->update(['tingkat' => null]);

    $siswa = Siswa::factory()->create();
    daftarkanSiswa($siswa, $kelasX, $sumber);

    $this->actingAs($admin)
        ->post(route('admin.naik-kelas.preview'), [
            'tahun_ajaran_sumber' => $sumber->id,
            'tahun_ajaran_target' => $target->id,
        ])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/NaikKelas/Index')
            ->where('preview.kelas.0.kelas_target_id', $kelasXI->id)
            ->has('preview.kelas_tujuan.X', 1)
            ->where('preview.kelas_tujuan.X.0.value', $kelasXI->id)
            ->where('preview.kelas_tujuan.X.0.label', $kelasXI->nama));
});
