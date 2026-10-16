<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('halaman login dapat diakses oleh tamu', function (): void {
    $this->get(route('auth.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Login'));
});

test('login dengan kredensial valid mengautentikasi dan mengarahkan ke dashboard', function (): void {
    $user = User::factory()->create([
        'email' => 'admin@ifsu.test',
        'password' => 'secret123',
        'role' => 'admin',
    ]);

    $this->post(route('auth.login'), [
        'email' => 'admin@ifsu.test',
        'password' => 'secret123',
    ])
        ->assertRedirect(route('app.dashboard'))
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

test('login dengan password salah ditolak', function (): void {
    User::factory()->create([
        'email' => 'admin@ifsu.test',
        'password' => 'secret123',
        'role' => 'admin',
    ]);

    $this->post(route('auth.login'), [
        'email' => 'admin@ifsu.test',
        'password' => 'salah123',
    ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login mewajibkan email dan password', function (): void {
    $this->post(route('auth.login'), [])
        ->assertSessionHasErrors(['email', 'password']);
});

test('login dibatasi setelah lima percobaan gagal', function (): void {
    User::factory()->create([
        'email' => 'admin@ifsu.test',
        'password' => 'secret123',
        'role' => 'admin',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('auth.login'), [
            'email' => 'admin@ifsu.test',
            'password' => 'salah123',
        ]);
    }

    $this->post(route('auth.login'), [
        'email' => 'admin@ifsu.test',
        'password' => 'secret123',
    ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('logout mengakhiri sesi dan kembali ke halaman login', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('app.auth.logout'))
        ->assertRedirect(route('auth.login'));

    $this->assertGuest();
});
