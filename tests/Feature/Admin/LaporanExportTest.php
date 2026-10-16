<?php

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('tamu diarahkan ke login saat membuka halaman laporan', function (): void {
    $this->get(route('admin.laporan.index'))->assertRedirect('/login');
});

test('pengguna non-admin ditolak membuka halaman laporan', function (): void {
    $guru = Guru::factory()->create();
    $user = User::factory()->create(['guru_id' => $guru->id]);

    $this->actingAs($user)
        ->get(route('admin.laporan.index'))
        ->assertForbidden();
});

test('admin dapat membuka halaman laporan dengan ringkasan jumlah data', function (): void {
    Jurusan::factory()->create();
    Guru::factory()->create();
    Siswa::factory()->create();
    TahunAjaran::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.laporan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Laporan/Index')
            ->where('counts.jurusan', 1)
            ->where('counts.guru', 1)
            ->where('counts.siswa', 1)
            ->where('counts.tahunAjaran', 1));
});

test('admin dapat mengunduh seluruh data sebagai XLSX', function (): void {
    Jurusan::factory()->create(['name' => 'Rekayasa Perangkat Lunak', 'kode' => 'RPL']);
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.laporan.export-xlsx'));

    $response->assertOk();
    $response->assertDownload('laporan-seluruh-data-'.date('Y-m-d').'.xlsx');
    $response->assertHeader(
        'content-type',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );
    expect(file_get_contents($response->getFile()->getPathname()))->toStartWith('PK');
});

test('admin dapat mengunduh seluruh data sebagai PDF', function (): void {
    Jurusan::factory()->create(['name' => 'Rekayasa Perangkat Lunak', 'kode' => 'RPL']);
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.laporan.export-pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertHeader(
        'content-disposition',
        'attachment; filename=laporan-seluruh-data-'.date('Y-m-d').'.pdf'
    );
    expect(file_get_contents($response->getFile()->getPathname()))->toStartWith('%PDF-');
});
