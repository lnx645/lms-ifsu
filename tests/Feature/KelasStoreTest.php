<?php

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;

test('tamu diarahkan ke login saat menambah kelas', function (): void {
    $this->post(route('admin.kelas.store'), [
        'nama' => 'X-RPL-1',
    ])->assertRedirect('/login');
});

test('pengguna non-admin dilarang menambah kelas', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.kelas.store'), [
            'nama' => 'X-RPL-1',
        ])->assertForbidden();
});

test('admin dapat menambah kelas root', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.kelas.store'), [
            'nama' => 'X',
            'deskripsi' => 'Kelas sepuluh',
            'active' => true,
        ])
        ->assertRedirect(route('admin.kelas.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('kelas', [
        'nama' => 'X',
        'deskripsi' => 'Kelas sepuluh',
        'parent_id' => null,
        'jurusan_id' => null,
        'guru_id' => null,
    ]);
});

test('admin dapat menambah kelas anak dengan jurusan dan wali kelas', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $parent = Kelas::factory()->create(['nama' => 'X']);
    $jurusan = Jurusan::factory()->create();
    $guru = Guru::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.kelas.store'), [
            'nama' => 'X-RPL-1',
            'parent_id' => $parent->id,
            'jurusan_id' => $jurusan->id,
            'guru_id' => $guru->id,
        ])
        ->assertRedirect(route('admin.kelas.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('kelas', [
        'nama' => 'X-RPL-1',
        'parent_id' => $parent->id,
        'jurusan_id' => $jurusan->id,
        'guru_id' => $guru->id,
    ]);
});

test('nama kelas wajib diisi', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.kelas.store'), [
            'nama' => '',
        ])
        ->assertSessionHasErrors('nama');
});

test('nama kelas harus unik dalam induk yang sama', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $parent = Kelas::factory()->create(['nama' => 'X']);
    Kelas::factory()->create(['nama' => 'X-RPL-1', 'parent_id' => $parent->id]);

    $this->actingAs($admin)
        ->post(route('admin.kelas.store'), [
            'nama' => 'X-RPL-1',
            'parent_id' => $parent->id,
        ])
        ->assertSessionHasErrors('nama');
});

test('nama kelas boleh sama di induk berbeda', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $parentA = Kelas::factory()->create(['nama' => 'X']);
    $parentB = Kelas::factory()->create(['nama' => 'XI']);

    $this->actingAs($admin)
        ->post(route('admin.kelas.store'), [
            'nama' => 'A',
            'parent_id' => $parentA->id,
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->post(route('admin.kelas.store'), [
            'nama' => 'A',
            'parent_id' => $parentB->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.kelas.index'));

    expect(Kelas::where('nama', 'A')->count())->toBe(2);
});

test('jurusan yang tidak ada ditolak', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.kelas.store'), [
            'nama' => 'X-RPL-1',
            'jurusan_id' => 999,
        ])
        ->assertSessionHasErrors('jurusan_id');
});

test('nama kelas harus unik di tingkat root (induk null)', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    Kelas::factory()->create(['nama' => 'X']);

    $this->actingAs($admin)
        ->post(route('admin.kelas.store'), [
            'nama' => 'X',
        ])
        ->assertSessionHasErrors('nama');
});

test('kelas tidak boleh menjadi induk bagi dirinya sendiri', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);

    $this->actingAs($admin)
        ->put(route('admin.kelas.update', $kelas), [
            'nama' => 'X-RPL-1',
            'parent_id' => $kelas->id,
        ])
        ->assertSessionHasErrors('parent_id');
});

test('kelas tidak boleh menjadi induk bagi turunannya', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $root = Kelas::factory()->create(['nama' => 'X']);
    $jurusan = Kelas::factory()->create(['nama' => 'X-RPL', 'parent_id' => $root->id]);
    $kelas = Kelas::factory()->create(['nama' => 'X-RPL-1', 'parent_id' => $jurusan->id]);

    $this->actingAs($admin)
        ->put(route('admin.kelas.update', $jurusan), [
            'nama' => 'X-RPL',
            'parent_id' => $kelas->id,
        ])
        ->assertSessionHasErrors('parent_id');
});

test('kelas yang dihapus ikut menghapus anaknya', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $root = Kelas::factory()->create(['nama' => 'X']);
    $node = Kelas::factory()->create(['nama' => 'X-RPL', 'parent_id' => $root->id]);
    Kelas::factory()->create(['nama' => 'X-RPL-1', 'parent_id' => $node->id]);

    $this->actingAs($admin)
        ->delete(route('admin.kelas.destroy', $root))
        ->assertRedirect(route('admin.kelas.index'));

    expect(Kelas::whereIn('id', [$root->id, $node->id])->count())->toBe(0);
    expect(Kelas::where('nama', 'X-RPL-1')->count())->toBe(0);
});
