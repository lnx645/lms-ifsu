<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('tamu diarahkan ke login saat menambah akun admin', function (): void {
    $this->post(route('admin.akun-admin.store'), [
        'name' => 'Admin Baru',
        'email' => 'admin2@ifsu.test',
        'password' => 'secret123',
    ])->assertRedirect('/login');
});

test('pengguna non-admin dilarang menambah akun admin', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.akun-admin.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin2@ifsu.test',
            'password' => 'secret123',
        ])->assertForbidden();
});

test('admin dapat menambah akun admin baru', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.akun-admin.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin2@ifsu.test',
            'password' => 'secret123',
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin.akun-admin.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'admin2@ifsu.test',
        'role' => 'admin',
        'is_admin' => true,
    ]);

    $created = User::where('email', 'admin2@ifsu.test')->first();
    expect(Hash::check('secret123', $created->password))->toBeTrue();
});

test('email akun admin harus unik', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin2@ifsu.test']);

    $this->actingAs($admin)
        ->post(route('admin.akun-admin.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin2@ifsu.test',
            'password' => 'secret123',
        ])
        ->assertSessionHasErrors('email');
});

test('password akun admin minimal 8 karakter', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.akun-admin.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin2@ifsu.test',
            'password' => '123',
        ])
        ->assertSessionHasErrors('password');
});

test('admin dapat memperbarui akun admin lain', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

    $this->actingAs($admin)
        ->put(route('admin.akun-admin.update', $target), [
            'name' => 'Admin Update',
            'email' => 'update@ifsu.test',
            'password' => '',
        ])
        ->assertRedirect(route('admin.akun-admin.index'))
        ->assertSessionHasNoErrors();

    $target->refresh();
    expect($target->name)->toBe('Admin Update');
    expect($target->email)->toBe('update@ifsu.test');
});

test('admin dapat memperbarui password akun admin lain', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

    $this->actingAs($admin)
        ->put(route('admin.akun-admin.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => 'passwordbaru',
        ])
        ->assertSessionHasNoErrors();

    $target->refresh();
    expect(Hash::check('passwordbaru', $target->password))->toBeTrue();
});

test('akun non-admin tidak dapat diperbarui lewat endpoint admin', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $nonAdmin = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.akun-admin.update', $nonAdmin), [
            'name' => 'Gagal',
            'email' => $nonAdmin->email,
        ])
        ->assertRedirect(route('admin.akun-admin.index'));

    $nonAdmin->refresh();
    expect($nonAdmin->name)->not->toBe('Gagal');
});

test('admin tidak dapat menghapus akun admin yang sedang digunakan', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    User::factory()->create(['role' => 'admin', 'is_admin' => true]);

    $this->actingAs($admin)
        ->delete(route('admin.akun-admin.destroy', $admin))
        ->assertRedirect(route('admin.akun-admin.index'));

    expect(User::where('role', 'admin')->count())->toBe(2);
});

test('admin terakhir tidak dapat dihapus', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

    $this->actingAs($admin)
        ->delete(route('admin.akun-admin.destroy', $admin))
        ->assertRedirect(route('admin.akun-admin.index'));

    expect(User::where('role', 'admin')->count())->toBe(1);
});

test('admin dapat menghapus akun admin lain', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    $target = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

    $this->actingAs($admin)
        ->delete(route('admin.akun-admin.destroy', $target))
        ->assertRedirect(route('admin.akun-admin.index'));

    expect(User::where('role', 'admin')->count())->toBe(1);
});

test('admin dapat menambah akun panitia', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.akun-admin.store'), [
            'name' => 'Panitia Satu',
            'email' => 'panitia1@ifsu.test',
            'password' => 'secret123',
            'role' => 'panitia',
        ])
        ->assertRedirect(route('admin.akun-admin.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'panitia1@ifsu.test',
        'role' => 'panitia',
        'is_admin' => false,
    ]);
});
