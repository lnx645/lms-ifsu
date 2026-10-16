<?php

use App\Models\BankOpsiSoal;
use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\OpsiSoal;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\User;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->matpelLain = Matpel::factory()->create(['name' => 'Fisika']);
    $this->kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);

    $this->guru = Guru::factory()->create();
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->admin = User::factory()->create(['role' => 'admin']);
});

function payloadBankSoal(array $overrides = []): array
{
    return array_merge([
        'tipe' => 'pg',
        'pertanyaan' => '<p>2 + 2 = ?</p>',
        'poin' => 10,
        'topik' => 'Aljabar',
        'kesulitan' => 'mudah',
        'opsi' => [
            ['teks' => '3', 'benar' => false],
            ['teks' => '4', 'benar' => true],
        ],
        'kunci_isian' => [],
    ], $overrides);
}

test('guru dapat membuka bank soal untuk matpel yang diampu', function (): void {
    BankSoal::factory()->for($this->matpel)->create(['guru_id' => $this->guru->id]);
    BankSoal::factory()->for($this->matpelLain)->create();

    $this->actingAs($this->guruUser)
        ->get(route('app.guru.bank-soal.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/BankSoal/Index')
            ->has('bank.data', 1)
            ->has('matpelOptions', 1));
});

test('guru dapat menambah soal ke bank', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.bank-soal.store'), payloadBankSoal([
            'matpel_id' => $this->matpel->id,
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $bank = BankSoal::where('matpel_id', $this->matpel->id)->first();
    expect($bank)->not->toBeNull()
        ->and($bank->guru_id)->toBe($this->guru->id)
        ->and($bank->opsi)->toHaveCount(2);
});

test('guru tidak dapat menambah soal untuk matpel yang tidak diampu', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.bank-soal.store'), payloadBankSoal([
            'matpel_id' => $this->matpelLain->id,
        ]))
        ->assertSessionHasErrors('matpel_id');
});

test('guru tidak dapat mengubah soal bank matpel lain', function (): void {
    $bank = BankSoal::factory()->for($this->matpelLain)->create();

    $this->actingAs($this->guruUser)
        ->put(route('app.guru.bank-soal.update', $bank), payloadBankSoal([
            'matpel_id' => $this->matpel->id,
        ]))
        ->assertNotFound();
});

test('guru dapat menghapus soal bank matpel yang diampu', function (): void {
    $bank = BankSoal::factory()->for($this->matpel)->create(['guru_id' => $this->guru->id]);

    $this->actingAs($this->guruUser)
        ->delete(route('app.guru.bank-soal.destroy', $bank))
        ->assertRedirect();

    $this->assertDatabaseMissing('bank_soals', ['id' => $bank->id]);
});

test('soal isian bank menormalkan kunci jawaban', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.bank-soal.store'), payloadBankSoal([
            'matpel_id' => $this->matpel->id,
            'tipe' => 'isian',
            'opsi' => [],
            'kunci_isian' => ['Jakarta', '  '],
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $bank = BankSoal::where('matpel_id', $this->matpel->id)->first();
    expect($bank->tipe)->toBe('isian')
        ->and($bank->kunci_isian)->toBe(['Jakarta']);
});

test('guru dapat menyalin soal bank ke ujian (snapshot)', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);
    $bank = BankSoal::factory()->for($this->matpel)->create(['tipe' => 'pg', 'poin' => 15]);
    BankOpsiSoal::factory()->for($bank)->create(['teks' => 'A', 'benar' => true]);
    BankOpsiSoal::factory()->for($bank)->create(['teks' => 'B', 'benar' => false]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.ambil-bank', $ujian), [
            'bank_soal_ids' => [$bank->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $soal = Soal::where('ujian_id', $ujian->id)->first();
    expect($soal)->not->toBeNull()
        ->and($soal->bank_soal_id)->toBe($bank->id)
        ->and($soal->poin)->toBe(15)
        ->and($soal->opsi)->toHaveCount(2);

    // Snapshot: edit bank tidak mengubah soal ujian.
    $bank->update(['poin' => 99]);
    expect($soal->fresh()->poin)->toBe(15);
});

test('guru tidak dapat menyalin soal bank matpel lain ke ujian', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);
    $bank = BankSoal::factory()->for($this->matpelLain)->create();

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.ambil-bank', $ujian), [
            'bank_soal_ids' => [$bank->id],
        ])
        ->assertSessionHasErrors('bank_soal_ids.0');
});

test('guru dapat menyimpan soal ujian ke bank', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);
    $soal = Soal::factory()->for($ujian)->create(['tipe' => 'pg', 'poin' => 20]);
    OpsiSoal::factory()->for($soal)->create(['teks' => 'A', 'benar' => true]);
    OpsiSoal::factory()->for($soal)->create(['teks' => 'B', 'benar' => false]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.simpan-bank', [$ujian, $soal]), [
            'topik' => 'Logika',
            'kesulitan' => 'sedang',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $bank = BankSoal::where('matpel_id', $this->matpel->id)->first();
    expect($bank)->not->toBeNull()
        ->and($bank->topik)->toBe('Logika')
        ->and($bank->poin)->toBe(20)
        ->and($bank->opsi)->toHaveCount(2);
});

test('guru dapat generate soal acak dari bank ke ujian', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
    ]);

    BankSoal::factory()->count(5)->for($this->matpel)->create([
        'tipe' => 'pg',
        'kesulitan' => 'mudah',
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.soal.generate-bank', $ujian), [
            'jumlah' => 3,
            'kesulitan' => 'mudah',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Soal::where('ujian_id', $ujian->id)->count())->toBe(3);
});

test('halaman bank soal guru memblokir siswa', function (): void {
    $siswaUser = User::factory()->create(['nisn' => Siswa::factory()->create()->nisn]);

    $this->actingAs($siswaUser)
        ->get(route('app.guru.bank-soal.index'))
        ->assertForbidden();
});

test('admin dapat mengelola bank soal lintas matpel', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.bank-soal.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/BankSoal/Index'));

    $this->actingAs($this->admin)
        ->post(route('admin.bank-soal.store'), payloadBankSoal([
            'matpel_id' => $this->matpelLain->id,
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('bank_soals', [
        'matpel_id' => $this->matpelLain->id,
        'guru_id' => null,
    ]);
});
