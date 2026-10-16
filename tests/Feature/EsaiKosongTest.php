<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Models\User;
use App\Services\UjianService;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->kelas = Kelas::factory()->create(['nama' => 'XI-RPL-1']);

    $this->guru = Guru::factory()->create();
    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);

    $this->siswa = Siswa::factory()->create();
    SiswaKelas::factory()->create([
        'siswa_nisn' => $this->siswa->nisn,
        'kelas_id' => $this->kelas->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'active' => true,
    ]);

    $this->penilaian = Penilaian::create([
        'nama' => 'UTS', 'tipe' => 'cbt', 'nilai_maks' => 100, 'bobot' => 2, 'aktif' => true, 'sumber' => 'uts',
    ]);
    $this->ujian = Ujian::factory()->create([
        'guru_id' => $this->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'penilaian_id' => $this->penilaian->id,
        'kategori' => 'uts',
        'status' => 'terbit',
        'nilai_maks' => 100,
    ]);
});

test('esai yang tidak dijawab dianggap 0, nilai tidak macet', function (): void {
    $soalEsai = Soal::factory()->for($this->ujian)->esai()->create(['poin' => 100]);

    $pengerjaan = UjianPengerjaan::create([
        'ujian_id' => $this->ujian->id,
        'siswa_nisn' => $this->siswa->nisn,
        'attempt_ke' => 1,
        'mulai_at' => now()->subHour(),
        'batas_at' => now()->subMinutes(30),
        'submitted_at' => now()->subMinutes(30),
        'status' => 'selesai',
    ]);
    // Sengaja TIDAK membuat JawabanSiswa untuk esai (siswa mengosongkan).

    $lengkap = app(UjianService::class)->rekalkulasiNilai($pengerjaan->fresh());

    expect($lengkap)->toBeTrue();

    $pengerjaan->refresh();
    expect($pengerjaan->nilai_total)->toBe(0.0);
    expect($pengerjaan->nilai_esai)->toBe(0.0);
});

test('esai yang dijawab tapi belum dikoreksi tetap pending', function (): void {
    $soalEsai = Soal::factory()->for($this->ujian)->esai()->create(['poin' => 100]);

    $pengerjaan = UjianPengerjaan::create([
        'ujian_id' => $this->ujian->id,
        'siswa_nisn' => $this->siswa->nisn,
        'attempt_ke' => 1,
        'mulai_at' => now()->subHour(),
        'batas_at' => now()->subMinutes(30),
        'submitted_at' => now()->subMinutes(30),
        'status' => 'selesai',
    ]);
    \App\Models\JawabanSiswa::create([
        'ujian_pengerjaan_id' => $pengerjaan->id,
        'soal_id' => $soalEsai->id,
        'jawaban_teks' => 'Jawaban siswa',
        // skor null = belum dikoreksi
    ]);

    $lengkap = app(UjianService::class)->rekalkulasiNilai($pengerjaan->fresh());

    expect($lengkap)->toBeFalse();

    $pengerjaan->refresh();
    expect($pengerjaan->nilai_total)->toBeNull();
});
