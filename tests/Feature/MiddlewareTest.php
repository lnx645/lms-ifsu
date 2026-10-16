<?php

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;

test('tamu mengakses halaman admin diarahkan ke login', function (): void {
    $this->get(route('admin.index'))->assertRedirect('/login');
});

test('tamu mengakses halaman app diarahkan ke login', function (): void {
    $this->get(route('app.dashboard'))->assertRedirect('/login');
});

test('pengguna non-admin tidak dapat mengakses halaman admin', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertForbidden();
});

test('admin dapat mengakses halaman admin', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.index'))
        ->assertOk();
});

test('admin yang mengakses halaman app diarahkan ke dashboard admin', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('app.dashboard'))
        ->assertRedirect(route('admin.index'));
});

test('pengguna guru dapat mengakses halaman app', function (): void {
    $guru = Guru::factory()->create();
    $user = User::factory()->create(['guru_id' => $guru->id]);

    $this->actingAs($user)
        ->get(route('app.dashboard'))
        ->assertOk();
});

test('pengguna siswa dapat mengakses halaman app', function (): void {
    $siswa = Siswa::factory()->create();
    $user = User::factory()->create(['nisn' => $siswa->nisn]);

    $this->actingAs($user)
        ->get(route('app.dashboard'))
        ->assertOk();
});

test('pengguna siswa tidak dapat mengakses halaman admin', function (): void {
    $siswa = Siswa::factory()->create();
    $user = User::factory()->create(['nisn' => $siswa->nisn]);

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertForbidden();
});

test('pengguna guru tidak dapat mengakses halaman admin', function (): void {
    $guru = Guru::factory()->create();
    $user = User::factory()->create(['guru_id' => $guru->id]);

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertForbidden();
});
