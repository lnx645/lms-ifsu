<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\KelompokBankSoal;
use App\Models\Matpel;
use App\Models\RuangUjian;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\User;

/**
 * E2E: alur sekolah beneran — guru bikin soal → serahkan ke panitia →
 * panitia atur jadwal → terbitkan → siswa kerjakan.
 */
test('e2e full: guru → panitia → siswa', function (): void {
    // --- Setup ---
    $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

    $tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $matpel = Matpel::factory()->create(['name' => 'Matematika E2E']);
    $kelas = Kelas::factory()->create(['nama' => 'X-E2E-1']);
    $guru = Guru::factory()->create();
    $guruUser = User::factory()->create(['guru_id' => $guru->id]);
    $guruKelas = GuruKelas::factory()->create([
        'guru_id' => $guru->id,
        'kelas_id' => $kelas->id,
        'matpel_id' => $matpel->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
        'aktif' => true,
    ]);

    $panitia = User::factory()->create(['role' => 'panitia']);

    // Buat ruang ujian untuk sesi.
    $ruang = RuangUjian::factory()->create(['nama' => 'Ruang E2E 1']);

    // --- 1. Admin buat akun panitia ---
    $this->actingAs($admin)
        ->post(route('admin.akun-admin.store'), [
            'name' => 'E2E Panitia',
            'email' => 'e2e-panitia@ifsu.test',
            'password' => 'password123',
            'role' => 'panitia',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $panitiaBaru = User::where('email', 'e2e-panitia@ifsu.test')->first();
    expect($panitiaBaru)->not->toBeNull()
        ->and($panitiaBaru->role)->toBe('panitia')
        ->and($panitiaBaru->gate_access)->toBeTrue();

    // --- 2. Guru buat bank soal ---
    $kelompok = $this->actingAs($guruUser)
        ->post(route('app.guru.bank-soal.kelompok.store'), [
            'nama' => 'E2E Bank',
            'matpel_id' => $guruKelas->matpel_id,
        ])
        ->assertRedirect();

    $bank = KelompokBankSoal::where('nama', 'E2E Bank')->first();
    expect($bank)->not->toBeNull();

    // Tambah 4 soal PG ke bank.
    for ($i = 1; $i <= 4; $i++) {
        $this->actingAs($guruUser)
            ->post(route('app.guru.bank-soal.kelompok.soal.store', $bank), [
                'tipe' => 'pg',
                'pertanyaan' => "Soal E2E nomor {$i}?",
                'poin' => 10,
                'opsi' => [
                    ['teks' => 'Jawaban benar', 'benar' => true],
                    ['teks' => 'Jawaban salah', 'benar' => false],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }
    expect($bank->soals()->count())->toBe(4);

    // --- 3. Guru buat ujian resmi via wizard ---
    $this->actingAs($guruUser)
        ->post(route('app.guru.ujian.wizard.info'), [
            'mode' => 'resmi',
            'guru_kelas_id' => $guruKelas->id,
            'kategori' => 'uts',
            'judul' => 'E2E Ujian Resmi',
            'durasi_menit' => 60,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $ujian = Ujian::where('judul', 'E2E Ujian Resmi')->first();
    expect($ujian)->not->toBeNull()
        ->and($ujian->status)->toBe('draft')
        ->and($ujian->kategori)->toBe('uts');

    // Generate paket dari bank.
    $this->actingAs($guruUser)
        ->post(route('app.guru.ujian.wizard.paket', $ujian), [
            'jumlah_paket' => 2,
            'soal_per_paket' => 2,
            'kelompok_bank_soal_id' => $bank->id,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($ujian->paketSoals()->count())->toBe(2);

    // Serahkan ke panitia.
    $this->actingAs($guruUser)
        ->post(route('app.guru.ujian.wizard.serahkan', $ujian))
        ->assertRedirect();

    expect($ujian->fresh()->status)->toBe('diajukan');

    // --- 4. Panitia atur jadwal & terbitkan ---
    $this->actingAs($panitia)
        ->get(route('app.panitia.ujian.show', $ujian))
        ->assertOk();

    $this->actingAs($panitia)
        ->post(route('app.panitia.ujian.sesi', $ujian), [
            'sesis' => [
                [
                    'nama' => 'Sesi 1',
                    'waktu_mulai' => now()->addDay()->format('Y-m-d\TH:i'),
                    'waktu_selesai' => now()->addDay()->addHours(2)->format('Y-m-d\TH:i'),
                    'ruang_ujian_id' => $ruang->id,
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($panitia)
        ->post(route('app.panitia.ujian.pengaturan', $ujian), [
            'tata_tertib' => "1. Jujur.\n2. Dilarang nyontek.\n3. Tepat waktu.",
            'kkm' => 75,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $ujian->refresh();
    expect($ujian->token)->not->toBeNull()
        ->and($ujian->token_released_at)->toBeNull() // token belum dibuka otomatis
        ->and($ujian->tata_tertib)->toContain('Jujur')
        ->and($ujian->kkm)->toBe(75);

    // Panitia buka token manual.
    $this->actingAs($panitia)
        ->post(route('app.panitia.ujian.buka-token', $ujian))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($ujian->fresh()->token_released_at)->not->toBeNull();

    // Panitia tutup token lagi.
    $this->actingAs($panitia)
        ->post(route('app.panitia.ujian.tutup-token', $ujian))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($ujian->fresh()->token_released_at)->toBeNull();

    // Buka lagi sebelum terbit.
    $this->actingAs($panitia)
        ->post(route('app.panitia.ujian.buka-token', $ujian))
        ->assertRedirect();

    $this->actingAs($panitia)
        ->post(route('app.panitia.ujian.terbit', $ujian))
        ->assertRedirect();

    $fresh = $ujian->fresh();
    $masalah = app(\App\Services\KelengkapanUjianService::class)->cek($fresh);
    expect($masalah)->toBe([], 'Kelengkapan gagal: '.implode(', ', $masalah));
    expect($fresh->status)->toBe('terbit');

    // --- 5. Verifikasi kelengkapan ---
    $masalah = app(\App\Services\KelengkapanUjianService::class)->cek($ujian->fresh());
    expect($masalah)->toBe([]);
});
