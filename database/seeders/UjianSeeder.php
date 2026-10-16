<?php

namespace Database\Seeders;

use App\Models\GuruKelas;
use App\Models\Penilaian;
use App\Models\PeriodeUjian;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use Illuminate\Database\Seeder;

class UjianSeeder extends Seeder
{
    /**
     * Contoh ujian CBT untuk demo: 1 kuis + 1 UTS per beberapa penugasan,
     * lengkap dengan soal 5 tipe. Termasuk periode UTS/UAS/USBK aktif.
     */
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::where('active', true)->first();

        if (! $tahunAjaran) {
            return;
        }

        // Window periode untuk ujian besar.
        foreach (['uts', 'uas', 'usbk'] as $kategori) {
            PeriodeUjian::updateOrCreate(
                ['kategori' => $kategori, 'tahun_ajaran_id' => $tahunAjaran->id, 'nama' => strtoupper($kategori).' Demo'],
                ['tanggal_mulai' => now()->subDay(), 'tanggal_selesai' => now()->addMonth()],
            );
        }

        $penugasan = GuruKelas::where('aktif', true)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->with(['guru', 'matpel'])
            ->take(3)
            ->get();

        foreach ($penugasan as $gk) {
            $this->buatUjian($gk, 'kuis', 'Kuis '.($gk->matpel?->name ?? 'Materi'));
            $this->buatUjian($gk, 'uts', 'UTS '.($gk->matpel?->name ?? 'Materi'));
        }
    }

    private function buatUjian(GuruKelas $gk, string $kategori, string $judul): void
    {
        $besar = in_array($kategori, ['uts', 'uas', 'usbk'], true);

        $penilaian = Penilaian::create([
            'nama' => strtoupper($kategori).': '.$judul,
            'deskripsi' => 'Contoh ujian demo.',
            'tipe' => 'cbt',
            'nilai_maks' => 100,
            'bobot' => $besar ? 2 : 1,
            'aktif' => true,
            'sumber' => $kategori,
        ]);

        $ujian = Ujian::create([
            'guru_id' => $gk->guru_id,
            'guru_kelas_id' => $gk->id,
            'penilaian_id' => $penilaian->id,
            'dibuat_oleh_admin' => false,
            'kategori' => $kategori,
            'judul' => $judul,
            'deskripsi' => 'Ujian contoh untuk demonstrasi fitur CBT.',
            'tanggal_mulai' => now()->subHour(),
            'tanggal_selesai' => now()->addWeek(),
            'durasi_menit' => $besar ? 90 : 30,
            'maks_attempt' => 1,
            'acak_soal' => $besar,
            'acak_opsi' => $besar,
            'tampilkan_hasil' => true,
            'wajib_fullscreen' => $besar,
            'maks_pelanggaran' => $besar ? 5 : 0,
            'nilai_maks' => 100,
            'bobot' => $besar ? 2 : 1,
            'status' => 'terbit',
            'token_pengelola' => 'guru',
        ]);

        // Soal PG
        $pg = $ujian->soals()->create([
            'tipe' => 'pg', 'pertanyaan' => '<p>Berapakah hasil dari 7 x 8?</p>', 'poin' => 20, 'urutan' => 1,
        ]);
        $pg->opsi()->createMany([
            ['teks' => '54', 'benar' => false, 'urutan' => 0],
            ['teks' => '56', 'benar' => true, 'urutan' => 1],
            ['teks' => '58', 'benar' => false, 'urutan' => 2],
            ['teks' => '64', 'benar' => false, 'urutan' => 3],
        ]);

        // Soal Benar/Salah
        $bs = $ujian->soals()->create([
            'tipe' => 'benar_salah', 'pertanyaan' => '<p>HTML adalah bahasa pemrograman.</p>', 'poin' => 20, 'urutan' => 2,
        ]);
        $bs->opsi()->createMany([
            ['teks' => 'Benar', 'benar' => false, 'urutan' => 0],
            ['teks' => 'Salah', 'benar' => true, 'urutan' => 1],
        ]);

        // Soal Multi-jawaban
        $multi = $ujian->soals()->create([
            'tipe' => 'multi', 'pertanyaan' => '<p>Manakah yang termasuk bahasa pemrograman?</p>', 'poin' => 20, 'urutan' => 3,
        ]);
        $multi->opsi()->createMany([
            ['teks' => 'Python', 'benar' => true, 'urutan' => 0],
            ['teks' => 'HTML', 'benar' => false, 'urutan' => 1],
            ['teks' => 'PHP', 'benar' => true, 'urutan' => 2],
            ['teks' => 'CSS', 'benar' => false, 'urutan' => 3],
        ]);

        // Soal Isian
        $ujian->soals()->create([
            'tipe' => 'isian',
            'pertanyaan' => '<p>Ibukota negara Indonesia adalah ...</p>',
            'poin' => 20,
            'urutan' => 4,
            'kunci_isian' => ['Jakarta', 'DKI Jakarta'],
            'isian_case_sensitive' => false,
        ]);

        // Soal Esai
        $ujian->soals()->create([
            'tipe' => 'esai',
            'pertanyaan' => '<p>Jelaskan perbedaan antara array dan objek.</p>',
            'poin' => 20,
            'urutan' => 5,
        ]);
    }
}
