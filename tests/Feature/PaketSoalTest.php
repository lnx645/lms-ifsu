<?php

use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\User;
use App\Services\KelengkapanUjianService;
use App\Services\PaketSoalService;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);

    $this->guru = Guru::factory()->create();
    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->ujian = Ujian::factory()->create([
        'guru_kelas_id' => $this->guruKelas->id,
        'guru_id' => $this->guru->id,
        'kategori' => 'usbk',
    ]);

    $this->bankSoals = BankSoal::factory()->count(12)->create([
        'matpel_id' => $this->matpel->id,
    ]);

    // Siswa untuk test distribusi paket.
    foreach (['1234567890', '1111111111', '2222222222'] as $nisn) {
        Siswa::factory()->create(['nisn' => $nisn]);
    }
});

test('generate otomatis membagi soal ke N paket secara merata', function (): void {
    $pakets = app(PaketSoalService::class)->generateOtomatis(
        $this->ujian, 3, 4, $this->bankSoals->pluck('id')->all()
    );

    expect($pakets)->toHaveCount(3)
        ->and($this->ujian->paketSoals()->count())->toBe(3);

    foreach ($pakets as $paket) {
        expect($paket->soals()->count())->toBe(4);
    }

    // Total soal terdistribusi = 12, tidak ada yang dobel.
    $soalIds = $this->ujian->paketSoals()->with('soals')->get()
        ->flatMap(fn ($p) => $p->soals->pluck('bank_soal_id'));
    expect($soalIds->unique()->count())->toBe(12);
});

test('generate otomatis menolak bila bank soal kurang', function (): void {
    expect(fn () => app(PaketSoalService::class)->generateOtomatis(
        $this->ujian, 3, 5, $this->bankSoals->pluck('id')->all()
    ))->toThrow(InvalidArgumentException::class, 'Bank soal kurang');
});

test('distribusi round-robin memberi paket berbeda ke siswa berurutan', function (): void {
    app(PaketSoalService::class)->generateOtomatis(
        $this->ujian, 3, 4, $this->bankSoals->pluck('id')->all()
    );

    $service = app(PaketSoalService::class);
    $p1 = $service->tentukanPaketUntukSiswa($this->ujian, null);
    $this->ujian->pengerjaans()->create([
        'siswa_nisn' => '1234567890', 'paket_soal_id' => $p1->id,
        'attempt_ke' => 1, 'status' => 'berlangsung',
        'mulai_at' => now(), 'batas_at' => now()->addHour(),
    ]);

    $p2 = $service->tentukanPaketUntukSiswa($this->ujian, null);

    expect($p1->id)->not->toBe($p2->id)
        ->and($p1->kode)->toBe('A')
        ->and($p2->kode)->toBe('B');
});

test('distribusi kembali ke paket A setelah satu putaran', function (): void {
    app(PaketSoalService::class)->generateOtomatis(
        $this->ujian, 2, 4, $this->bankSoals->pluck('id')->all()
    );

    $service = app(PaketSoalService::class);
    $pakets = $this->ujian->paketSoals()->orderBy('kode')->get();

    foreach (['1111111111', '2222222222'] as $i => $nisn) {
        $p = $service->tentukanPaketUntukSiswa($this->ujian, null);
        $this->ujian->pengerjaans()->create([
            'siswa_nisn' => $nisn, 'paket_soal_id' => $p->id,
            'attempt_ke' => 1, 'status' => 'berlangsung',
            'mulai_at' => now(), 'batas_at' => now()->addHour(),
        ]);
    }

    $p3 = $service->tentukanPaketUntukSiswa($this->ujian, null);
    expect($p3->kode)->toBe('A');
});

test('kelengkapan ujian resmi menolak terbit bila checklist belum penuh', function (): void {
    $masalah = app(KelengkapanUjianService::class)->cek($this->ujian);

    // Ujian factory usbk tanpa paket/sesi/token/tata tertib/kkm.
    expect($masalah)->not->toBeEmpty()
        ->and($masalah)->toContain('Buat paket soal (minimal 2 paket, misal A dan B).');
});

test('kelengkapan kuis hanya butuh minimal satu soal', function (): void {
    $kuis = Ujian::factory()->create([
        'guru_kelas_id' => $this->guruKelas->id,
        'guru_id' => $this->guru->id,
        'kategori' => 'kuis',
    ]);

    expect(app(KelengkapanUjianService::class)->cek($kuis))
        ->toContain('Tambahkan minimal satu soal sebelum menerbitkan.');
});

test('panitia bisa akses dashboard ujian resmi, guru biasa ditolak', function (): void {
    $panitia = User::factory()->create(['role' => 'panitia']);

    $this->actingAs($panitia)
        ->get(route('app.panitia.ujian.index'))
        ->assertOk();

    $guruUser = User::factory()->create(['guru_id' => $this->guru->id]);
    $this->actingAs($guruUser)
        ->get(route('app.panitia.ujian.index'))
        ->assertForbidden();
});

test('role panitia terbaca dari kolom users.role', function (): void {
    $panitia = User::factory()->create(['role' => 'panitia']);

    expect($panitia->role)->toBe('panitia')
        ->and($panitia->isRole('panitia'))->toBeTrue();
});
