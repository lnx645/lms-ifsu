<?php

use App\Models\Siswa;
use App\Models\User;

function buatAdminSiswa(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('tamu diarahkan ke login saat menambah siswa', function (): void {
    $this->post(route('admin.siswa.store'), [
        'nisn' => '1234567890',
        'nis' => '12345',
        'nama_lengkap' => 'Budi Santoso',
    ])->assertRedirect('/login');
});

test('pengguna non-admin dilarang menambah siswa', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.siswa.store'), [
            'nisn' => '1234567890',
            'nis' => '12345',
            'nama_lengkap' => 'Budi Santoso',
        ])->assertForbidden();
});

test('admin dapat menambah siswa beserta akun otomatis', function (): void {
    $admin = buatAdminSiswa();

    $this->actingAs($admin)
        ->post(route('admin.siswa.store'), [
            'nisn' => '1234567890',
            'nis' => '12345',
            'nama_lengkap' => 'Budi Santoso',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2008-05-15',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jl. Merdeka No. 1',
            'is_aktif' => true,
        ])
        ->assertRedirect(route('admin.siswa.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('siswa', [
        'nisn' => '1234567890',
        'nis' => '12345',
        'nama_lengkap' => 'Budi Santoso',
        'jenis_kelamin' => 'L',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'siswa_1234567890@sch.id',
        'role' => 'siswa',
        'nisn' => '1234567890',
    ]);

    expect(User::where('nisn', '1234567890')->count())->toBe(1);
});

test('nisn yang sudah dipakai ditolak', function (): void {
    $admin = buatAdminSiswa();
    Siswa::factory()->create(['nisn' => '1234567890']);

    $this->actingAs($admin)
        ->post(route('admin.siswa.store'), [
            'nisn' => '1234567890',
            'nis' => '54321',
            'nama_lengkap' => 'Budi Santoso',
        ])
        ->assertSessionHasErrors('nisn');
});

test('nis yang sudah dipakai ditolak', function (): void {
    $admin = buatAdminSiswa();
    Siswa::factory()->create(['nis' => '12345']);

    $this->actingAs($admin)
        ->post(route('admin.siswa.store'), [
            'nisn' => '0987654321',
            'nis' => '12345',
            'nama_lengkap' => 'Budi Santoso',
        ])
        ->assertSessionHasErrors('nis');
});

test('nama lengkap wajib diisi', function (): void {
    $admin = buatAdminSiswa();

    $this->actingAs($admin)
        ->post(route('admin.siswa.store'), [
            'nisn' => '1234567890',
            'nis' => '12345',
            'nama_lengkap' => '',
        ])
        ->assertSessionHasErrors('nama_lengkap');
});

test('nisn wajib diisi', function (): void {
    $admin = buatAdminSiswa();

    $this->actingAs($admin)
        ->post(route('admin.siswa.store'), [
            'nis' => '12345',
            'nama_lengkap' => 'Budi Santoso',
        ])
        ->assertSessionHasErrors('nisn');
});
