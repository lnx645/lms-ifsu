<?php

use App\Models\BeritaAcaraUjian;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Notifikasi;
use App\Models\OpsiSoal;
use App\Models\Penilaian;
use App\Models\RuangUjian;
use App\Models\SesiPengawas;
use App\Models\SesiUjian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Models\User;
use App\Services\AnalisisButirSoalService;
use App\Services\SoalImportService;
use App\Services\UjianService;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'IPA']);
    $this->kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);

    $this->guru = Guru::factory()->create();
    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id, 'role' => 'guru']);
    $this->adminUser = User::factory()->create(['role' => 'admin']);

    $this->siswa = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswa->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);
    $this->siswaUser = User::factory()->create(['nisn' => $this->siswa->nisn, 'role' => 'siswa']);
});

function buatUjianAkademik($ctx, array $attr = []): Ujian
{
    $penilaian = Penilaian::create([
        'nama' => 'CBT', 'tipe' => 'cbt', 'nilai_maks' => 100, 'bobot' => 1, 'aktif' => true, 'sumber' => 'kuis',
    ]);

    return Ujian::factory()->create([
        'guru_id' => $ctx->guru->id,
        'guru_kelas_id' => $ctx->guruKelas->id,
        'penilaian_id' => $penilaian->id,
        'kategori' => 'kuis',
        'status' => 'terbit',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'durasi_menit' => 60,
        ...$attr,
    ]);
}

// ------------------------------------------------------------------
// 4. Kisi-kisi / KD
// ------------------------------------------------------------------

test('soal dapat menyimpan kode KD dan indikator', function (): void {
    $ujian = buatUjianAkademik($this);

    $soal = Soal::factory()->create([
        'ujian_id' => $ujian->id,
        'kode_kd' => 'KD 3.1',
        'indikator' => 'Menyebutkan ibukota negara',
    ]);

    expect($soal->fresh()->kode_kd)->toBe('KD 3.1')
        ->and($soal->fresh()->indikator)->toBe('Menyebutkan ibukota negara');
});

// ------------------------------------------------------------------
// 9. Ruang ujian
// ------------------------------------------------------------------

test('admin dapat CRUD ruang ujian; guru tidak bisa', function (): void {
    // Guru tidak lagi boleh mengelola ruang (404).
    $this->actingAs($this->guruUser)
        ->post('/app/guru/ruang', [
            'kode' => 'R101', 'nama' => 'Ruang 101', 'kapasitas' => 40, 'lokasi' => 'Gedung A',
        ])
        ->assertNotFound();

    // Admin boleh.
    $this->actingAs($this->adminUser);

    $this->post('/admin/ruang', [
        'kode' => 'R101', 'nama' => 'Ruang 101', 'kapasitas' => 40, 'lokasi' => 'Gedung A',
    ])->assertRedirect();

    $ruang = RuangUjian::where('kode', 'R101')->first();
    expect($ruang)->not->toBeNull();

    $this->put("/admin/ruang/{$ruang->id}", [
        'kode' => 'R101', 'nama' => 'Ruang 101 Baru', 'kapasitas' => 35, 'lokasi' => null,
    ])->assertRedirect();
    expect($ruang->fresh()->nama)->toBe('Ruang 101 Baru');

    $this->delete("/admin/ruang/{$ruang->id}")->assertRedirect();
    expect(RuangUjian::find($ruang->id))->toBeNull();
});

test('sesi dapat diberi ruang', function (): void {
    $ujian = buatUjianAkademik($this);
    $sesi = SesiUjian::factory()->create(['ujian_id' => $ujian->id]);
    $ruang = RuangUjian::factory()->create();
    $this->actingAs($this->guruUser);

    $this->put("/app/guru/ujian/{$ujian->id}/sesi/{$sesi->id}/ruang", [
        'ruang_ujian_id' => $ruang->id,
    ])->assertRedirect();
    expect($sesi->fresh()->ruang_ujian_id)->toBe($ruang->id);
});

// ------------------------------------------------------------------
// 1 & 8. Kartu peserta & daftar hadir (PDF)
// ------------------------------------------------------------------

test('kartu peserta dan daftar hadir menghasilkan PDF', function (): void {
    $ujian = buatUjianAkademik($this, ['mode_sesi' => 'bebas']);
    $sesi = SesiUjian::factory()->create(['ujian_id' => $ujian->id]);
    $this->actingAs($this->guruUser);

    $this->get("/app/guru/ujian/{$ujian->id}/sesi/{$sesi->id}/kartu-peserta")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get("/app/guru/ujian/{$ujian->id}/sesi/{$sesi->id}/daftar-hadir")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

// ------------------------------------------------------------------
// 2. Berita acara
// ------------------------------------------------------------------

test('berita acara tersimpan dengan statistik otomatis dan dapat dicetak', function (): void {
    $ujian = buatUjianAkademik($this);
    $sesi = SesiUjian::factory()->create(['ujian_id' => $ujian->id]);
    UjianPengerjaan::factory()->create([
        'ujian_id' => $ujian->id, 'siswa_nisn' => $this->siswa->nisn,
        'sesi_ujian_id' => $sesi->id, 'status' => 'selesai',
    ]);
    $this->actingAs($this->guruUser);

    $this->post("/app/guru/ujian/{$ujian->id}/berita-acara", [
        'sesi_ujian_id' => $sesi->id,
        'tanggal' => now()->toDateString(),
        'nama_pengawas' => 'Pak Budi',
        'catatan' => 'Berjalan lancar.',
    ])->assertRedirect();

    $ba = BeritaAcaraUjian::where('ujian_id', $ujian->id)->first();
    expect($ba)->not->toBeNull()
        ->and($ba->jumlah_hadir)->toBe(1)
        ->and($ba->nama_pengawas)->toBe('Pak Budi');

    $this->get("/app/guru/ujian/{$ujian->id}/berita-acara/{$ba->id}/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

// ------------------------------------------------------------------
// 3. Analisis butir soal
// ------------------------------------------------------------------

test('analisis butir soal menghitung kesukaran dan daya beda', function (): void {
    $ujian = buatUjianAkademik($this);
    $soal = Soal::factory()->create(['ujian_id' => $ujian->id, 'tipe' => 'pg', 'poin' => 10]);
    $opsiBenar = OpsiSoal::factory()->create(['soal_id' => $soal->id, 'benar' => true]);
    OpsiSoal::factory()->create(['soal_id' => $soal->id, 'benar' => false]);

    // 4 responden: 3 benar, 1 salah.
    foreach (range(1, 4) as $i) {
        $sw = Siswa::factory()->create();
        $p = UjianPengerjaan::factory()->create([
            'ujian_id' => $ujian->id, 'siswa_nisn' => $sw->nisn, 'status' => 'selesai',
        ]);
        JawabanSiswa::factory()->create([
            'ujian_pengerjaan_id' => $p->id, 'soal_id' => $soal->id,
            'opsi_dipilih' => [$opsiBenar->id],
            'benar' => $i <= 3, 'skor' => $i <= 3 ? 10 : 0,
        ]);
    }

    $hasil = app(AnalisisButirSoalService::class)->analisis($ujian);

    expect($hasil['jumlah_responden'])->toBe(4)
        ->and($hasil['soal'])->toHaveCount(1)
        ->and($hasil['soal'][0]['taraf_kesukaran'])->toBe(0.75)
        ->and($hasil['soal'][0]['kategori_kesukaran'])->toBe('mudah');
});

// ------------------------------------------------------------------
// 5. Import soal Excel
// ------------------------------------------------------------------

test('impor soal dari Excel berhasil', function (): void {
    $ujian = buatUjianAkademik($this);
    $service = app(SoalImportService::class);

    $path = tempnam(sys_get_temp_dir(), 'import-').'.xlsx';
    $writer = new Writer(new Options);
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(SoalImportService::HEADER));
    $writer->addRow(Row::fromValues([
        'pg', 'Ibukota Indonesia?', '10', 'KD 3.1', 'Menyebutkan ibukota',
        'Jakarta', 'Bandung', 'Surabaya', '', '', 'A', '',
    ]));
    $writer->addRow(Row::fromValues([
        'isian', '7 x 8 = ?', '5', '', '', '', '', '', '', '', '56', '',
    ]));
    $writer->addRow(Row::fromValues([
        'pg', '', '10', '', '', 'A', 'B', '', '', '', 'A', '',
    ])); // gagal: pertanyaan kosong
    $writer->close();

    $hasil = $service->impor($ujian, $path);

    expect($hasil['berhasil'])->toBe(2)
        ->and($hasil['gagal'])->toBe(1)
        ->and($ujian->soals()->count())->toBe(2);

    $pg = $ujian->soals()->where('tipe', 'pg')->first();
    expect($pg->kode_kd)->toBe('KD 3.1')
        ->and($pg->opsi()->where('benar', true)->count())->toBe(1);
});

// ------------------------------------------------------------------
// 6. Remedial
// ------------------------------------------------------------------

test('siswa di bawah KKM mendapat kesempatan remedial', function (): void {
    $ujian = buatUjianAkademik($this, ['kkm' => 75, 'maks_attempt' => 1]);
    UjianPengerjaan::factory()->create([
        'ujian_id' => $ujian->id, 'siswa_nisn' => $this->siswa->nisn,
        'status' => 'selesai', 'nilai_total' => 60, 'is_remedial' => false,
    ]);

    expect($ujian->butuhRemedial($this->siswa->nisn))->toBeTrue();

    $this->actingAs($this->siswaUser);
    $this->post("/app/ujian/{$ujian->id}/mulai", ['setuju_tata_tertib' => true])->assertRedirect();

    $remedial = UjianPengerjaan::where('ujian_id', $ujian->id)
        ->where('siswa_nisn', $this->siswa->nisn)
        ->where('is_remedial', true)->first();
    expect($remedial)->not->toBeNull();
});

test('siswa di atas KKM tidak dapat remedial tambahan', function (): void {
    $ujian = buatUjianAkademik($this, ['kkm' => 75, 'maks_attempt' => 1]);
    UjianPengerjaan::factory()->create([
        'ujian_id' => $ujian->id, 'siswa_nisn' => $this->siswa->nisn,
        'status' => 'selesai', 'nilai_total' => 90, 'is_remedial' => false,
    ]);

    expect($ujian->butuhRemedial($this->siswa->nisn))->toBeFalse();
});

// ------------------------------------------------------------------
// 7. Duplikasi ujian
// ------------------------------------------------------------------

test('duplikat ujian menyalin soal opsi dan sesi sebagai draft', function (): void {
    $ujian = buatUjianAkademik($this, ['status' => 'terbit']);
    $soal = Soal::factory()->create(['ujian_id' => $ujian->id, 'tipe' => 'pg']);
    OpsiSoal::factory()->count(3)->create(['soal_id' => $soal->id]);
    SesiUjian::factory()->create(['ujian_id' => $ujian->id]);

    $baru = app(UjianService::class)->duplikat($ujian);

    expect($baru->id)->not->toBe($ujian->id)
        ->and($baru->status)->toBe('draft')
        ->and($baru->judul)->toContain('(Salinan)')
        ->and($baru->soals()->count())->toBe(1)
        ->and($baru->soals()->first()->opsi()->count())->toBe(3)
        ->and($baru->sesis()->count())->toBe(1);
});

// ------------------------------------------------------------------
// 10. Reset pengerjaan
// ------------------------------------------------------------------

test('guru dapat mereset pengerjaan siswa', function (): void {
    $ujian = buatUjianAkademik($this);
    $pengerjaan = UjianPengerjaan::factory()->create([
        'ujian_id' => $ujian->id, 'siswa_nisn' => $this->siswa->nisn, 'status' => 'selesai',
    ]);
    $soal = Soal::factory()->create(['ujian_id' => $ujian->id]);
    JawabanSiswa::factory()->create([
        'ujian_pengerjaan_id' => $pengerjaan->id, 'soal_id' => $soal->id,
    ]);
    $this->actingAs($this->guruUser);

    $this->delete("/app/guru/ujian/{$ujian->id}/pengerjaan/{$pengerjaan->id}/reset")->assertRedirect();

    expect(UjianPengerjaan::find($pengerjaan->id))->toBeNull()
        ->and(JawabanSiswa::where('ujian_pengerjaan_id', $pengerjaan->id)->count())->toBe(0);
});

// ------------------------------------------------------------------
// 11. Upload media soal
// ------------------------------------------------------------------

test('guru dapat mengunggah dan menghapus media soal', function (): void {
    $ujian = buatUjianAkademik($this);
    $soal = Soal::factory()->create(['ujian_id' => $ujian->id]);
    $this->actingAs($this->guruUser);

    $file = UploadedFile::fake()->image('gambar.jpg');
    $this->post("/app/guru/ujian/{$ujian->id}/soal/{$soal->id}/media", ['media' => $file])->assertRedirect();

    $soal->refresh();
    expect($soal->media_path)->not->toBeNull()
        ->and($soal->media_type)->toBe('gambar');

    $this->delete("/app/guru/ujian/{$ujian->id}/soal/{$soal->id}/media")->assertRedirect();
    expect($soal->fresh()->media_path)->toBeNull();
});

// ------------------------------------------------------------------
// 12. Notifikasi
// ------------------------------------------------------------------

test('notifikasi jadwal terkirim ke siswa kelas saat ujian diterbitkan', function (): void {
    $ujian = buatUjianAkademik($this, ['status' => 'draft']);
    Soal::factory()->create(['ujian_id' => $ujian->id]);
    $this->actingAs($this->guruUser);

    $this->post("/app/guru/ujian/{$ujian->id}/terbit")->assertRedirect();

    $notif = Notifikasi::where('user_id', $this->siswaUser->id)->first();
    expect($notif)->not->toBeNull()
        ->and($notif->tipe)->toBe('jadwal');
});

test('siswa dapat melihat dan menandai notifikasi dibaca', function (): void {
    Notifikasi::factory()->create(['user_id' => $this->siswaUser->id, 'dibaca_at' => null]);
    $this->actingAs($this->siswaUser);

    $this->get('/app/notifikasi')->assertOk();

    $notif = Notifikasi::where('user_id', $this->siswaUser->id)->first();
    $this->post("/app/notifikasi/{$notif->id}/baca")->assertRedirect();
    expect($notif->fresh()->dibaca_at)->not->toBeNull();
});

test('ruang yang bentrok waktu dengan sesi lain ditolak', function (): void {
    $ujian = buatUjianAkademik($this);
    $ruang = RuangUjian::factory()->create();

    $sesi1 = SesiUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'waktu_mulai' => now()->addHour(),
        'waktu_selesai' => now()->addHours(2),
        'ruang_ujian_id' => $ruang->id,
    ]);
    $sesi2 = SesiUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'waktu_mulai' => now()->addHours(1)->addMinutes(30), // tumpang tindih
        'waktu_selesai' => now()->addHours(3),
    ]);

    $this->actingAs($this->guruUser);
    $this->put("/app/guru/ujian/{$ujian->id}/sesi/{$sesi2->id}/ruang", [
        'ruang_ujian_id' => $ruang->id,
    ])->assertRedirect();

    // Ditolak: ruang tidak berubah.
    expect($sesi2->fresh()->ruang_ujian_id)->toBeNull();

    // Sesi yang waktunya tidak tumpang tindih boleh memakai ruang yang sama.
    $sesi3 = SesiUjian::factory()->create([
        'ujian_id' => $ujian->id,
        'waktu_mulai' => now()->addHours(3),
        'waktu_selesai' => now()->addHours(4),
    ]);
    $this->put("/app/guru/ujian/{$ujian->id}/sesi/{$sesi3->id}/ruang", [
        'ruang_ujian_id' => $ruang->id,
    ])->assertRedirect();
    expect($sesi3->fresh()->ruang_ujian_id)->toBe($ruang->id);
});
