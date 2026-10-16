<?php

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;

test('user dengan role admin berperan sebagai admin', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    expect($user->role)->toBe('admin');
    expect($user->isRole('admin'))->toBeTrue();
});

test('user tanpa guru/siswa dan tanpa role bukan admin', function (): void {
    $user = User::factory()->create();

    expect($user->role)->toBeFalse();
    expect($user->isRole('admin'))->toBeFalse();
});

test('user yang terhubung ke guru berperan sebagai guru', function (): void {
    $guru = Guru::factory()->create();
    $user = User::factory()->create(['guru_id' => $guru->id]);

    expect($user->role)->toBe('guru');
    expect($user->isRole('guru'))->toBeTrue();
});

test('user yang terhubung ke siswa berperan sebagai siswa', function (): void {
    $siswa = Siswa::factory()->create();
    $user = User::factory()->create(['nisn' => $siswa->nisn]);

    expect($user->role)->toBe('siswa');
    expect($user->isRole('siswa'))->toBeTrue();
});

test('relasi guru menang atas kolom role admin', function (): void {
    $guru = Guru::factory()->create();
    $user = User::factory()->create(['role' => 'admin', 'guru_id' => $guru->id]);

    expect($user->role)->toBe('guru');
});

test('role attribute tidak menghitung relasi yang belum dimuat', function (): void {
    $guru = Guru::factory()->create();
    $user = User::factory()->create(['guru_id' => $guru->id]);

    expect($user->role)->toBe('guru');
});

test('isRole mengembalikan false untuk peran lain', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    expect($user->isRole('guru'))->toBeFalse();
    expect($user->isRole('siswa'))->toBeFalse();
});

test('role admin menghasilkan gate access', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    expect($user->gate_access)->toBeTrue();
});

test('user tanpa relasi tidak memiliki gate access', function (): void {
    $user = User::factory()->create();

    expect($user->gate_access)->toBeFalse();
});
