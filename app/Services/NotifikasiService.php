<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\SesiUjian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Support\Collection;

class NotifikasiService
{
    /**
     * Kirim notifikasi jadwal ujian ke seluruh siswa kelas ujian.
     */
    public function jadwalUjian(Ujian $ujian): int
    {
        $users = $this->userSiswaKelas($ujian);
        $count = 0;

        foreach ($users as $user) {
            Notifikasi::create([
                'user_id' => $user->id,
                'tipe' => 'jadwal',
                'judul' => 'Jadwal ujian: '.$ujian->judul,
                'pesan' => $this->pesanJadwal($ujian),
                'tautan' => route('app.siswa.ujian.index'),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Notifikasi penugasan sesi terjadwal ke siswa tertentu.
     */
    public function jatahSesi(SesiUjian $sesi, string $siswaNisn): void
    {
        $user = User::where('nisn', $siswaNisn)->first();

        if ($user === null) {
            return;
        }

        Notifikasi::create([
            'user_id' => $user->id,
            'tipe' => 'jadwal',
            'judul' => 'Anda dijadwalkan: '.$sesi->nama,
            'pesan' => sprintf(
                'Ujian "%s" — %s pada %s s.d. %s%s.',
                $sesi->ujian->judul,
                $sesi->nama,
                $sesi->waktu_mulai->translatedFormat('d M Y H:i'),
                $sesi->waktu_selesai->translatedFormat('d M Y H:i'),
                $sesi->ruang ? ' di '.$sesi->ruang->nama : ''
            ),
            'tautan' => route('app.siswa.ujian.index'),
        ]);
    }

    /**
     * Notifikasi nilai sudah keluar / perlu remedial.
     */
    public function hasilUjian(Ujian $ujian, string $siswaNisn, float $nilai): void
    {
        $user = User::where('nisn', $siswaNisn)->first();

        if ($user === null) {
            return;
        }

        $remedial = $ujian->kkm !== null && $nilai < $ujian->kkm;

        Notifikasi::create([
            'user_id' => $user->id,
            'tipe' => 'nilai',
            'judul' => $remedial ? 'Remedial: '.$ujian->judul : 'Nilai keluar: '.$ujian->judul,
            'pesan' => $remedial
                ? sprintf('Nilai Anda %.1f di bawah KKM %d. Silakan ikuti remedial.', $nilai, $ujian->kkm)
                : sprintf('Nilai ujian "%s": %.1f.', $ujian->judul, $nilai),
            'tautan' => route('app.siswa.ujian.index'),
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    private function userSiswaKelas(Ujian $ujian): Collection
    {
        $kelasId = $ujian->guruKelas?->kelas_id;

        if ($kelasId === null) {
            return collect();
        }

        $nisns = SiswaKelas::where('kelas_id', $kelasId)
            ->where('active', true)
            ->pluck('siswa_nisn');

        return User::whereIn('nisn', $nisns)->get();
    }

    private function pesanJadwal(Ujian $ujian): string
    {
        $parts = ['Ujian "'.$ujian->judul.'"'];

        if ($ujian->tanggal_mulai) {
            $parts[] = 'mulai '.$ujian->tanggal_mulai->translatedFormat('d M Y H:i');
        }

        if ($ujian->memakaiSesi()) {
            $sesis = $ujian->sesis()->with('ruang')->get();
            $parts[] = $sesis->count().' sesi: '.$sesis->map(
                fn ($s): string => $s->nama.' ('.$s->waktu_mulai->format('H:i').'-'.$s->waktu_selesai->format('H:i').($s->ruang ? ', '.$s->ruang->nama : '').')'
            )->implode(', ');
        }

        return implode('. ', $parts).'.';
    }
}
