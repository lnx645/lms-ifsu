<?php

namespace App\Services;

use App\Models\DetailPenilaian;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Matpel;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\TugasPengumpulan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanExportService
{
    private ?int $tahunAjaranId = null;

    public function setTahunAjaran(?int $id): self
    {
        $this->tahunAjaranId = $id;

        return $this;
    }

    public function getTahunAjaranId(): ?int
    {
        return $this->tahunAjaranId;
    }

    /**
     * Dataset seluruh data untuk laporan (sheet XLSX / bagian PDF).
     *
     * @return array<int, array{title: string, headers: string[], rows: array<int, array<int, mixed>>}>
     */
    public function datasets(): array
    {
        return [
            $this->sheet('Jurusan', ['Kode', 'Nama Jurusan'], $this->jurusan()),
            $this->sheet('Mata Pelajaran', ['Nama', 'Deskripsi'], $this->matpel()),
            $this->sheet('Tahun Ajaran', ['Tahun Ajaran', 'Status'], $this->tahunAjaran()),
            $this->sheet('Kelas', ['Nama Kelas', 'Tingkat', 'Jurusan', 'Wali Kelas', 'Status'], $this->kelas()),
            $this->sheet('Guru', ['NIP', 'Nama Lengkap', 'Pend. Terakhir', 'JK', 'Alamat', 'Status'], $this->guru()),
            $this->sheet('Siswa', ['NISN', 'NIS', 'Nama Lengkap', 'Tempat Lahir', 'Tgl Lahir', 'JK', 'Alamat', 'Kelas', 'Status'], $this->siswa()),
            $this->sheet('Penugasan Guru-Kelas', ['Guru', 'Kelas', 'Mata Pelajaran', 'Tahun Ajaran', 'Status'], $this->penugasan()),
            $this->sheet('Materi', ['Judul', 'Guru', 'Kelas', 'Mata Pelajaran', 'File', 'Ukuran (KB)', 'Dibuat'], $this->materi()),
            $this->sheet('Tugas', ['Judul', 'Guru', 'Kelas', 'Mata Pelajaran', 'Terbit', 'Deadline', 'Jenis', 'Poin', 'File'], $this->tugas()),
            $this->sheet('Pengumpulan Tugas', ['Tugas', 'Siswa', 'Waktu Kumpul', 'Nilai', 'Jawaban', 'File'], $this->pengumpulan()),
            $this->sheet('Penilaian', ['Nama', 'Tipe', 'Nilai Maks', 'Bobot', 'Sumber', 'Status'], $this->penilaian()),
            $this->sheet('Detail Nilai', ['Penilaian', 'Siswa', 'Kelas', 'Mata Pelajaran', 'Thn Ajaran', 'Guru', 'Nilai', 'Keterangan'], $this->detailNilai()),
            $this->sheet('Riwayat Kelas Siswa', ['Siswa', 'Kelas', 'Tahun Ajaran', 'Aktif', 'Pertama Masuk'], $this->siswaKelas()),
            $this->sheet('Akun Pengguna', ['Nama', 'Email', 'Peran', 'Terhubung Dengan'], $this->users()),
        ];
    }

    /**
     * Hitung lebar kolom optimal berdasarkan isi data.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, float>
     */
    public function hitungLebarKolom(array $headers, array $rows): array
    {
        $widths = array_map(fn ($h) => mb_strlen((string) $h) + 4, $headers);

        foreach ($rows as $row) {
            foreach ($row as $col => $val) {
                $len = mb_strlen((string) $val) + 4;
                if (($widths[$col] ?? 0) < $len) {
                    $widths[$col] = $len;
                }
            }
        }

        // Maks 50 karakter
        return array_map(fn ($w) => min($w, 50), $widths);
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $taId = $this->tahunAjaranId;

        return [
            'jurusan' => Jurusan::count(),
            'matpel' => Matpel::count(),
            'tahunAjaran' => TahunAjaran::count(),
            'kelas' => $taId ? Kelas::whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $taId))->count() : Kelas::count(),
            'guru' => Guru::count(),
            'siswa' => $taId
                ? DB::table('siswa_kelas')->where('tahun_ajaran_id', $taId)->distinct('siswa_nisn')->count('siswa_nisn')
                : Siswa::count(),
            'penugasan' => $taId ? GuruKelas::where('tahun_ajaran_id', $taId)->count() : GuruKelas::count(),
            'materi' => $taId ? Materi::whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $taId))->count() : Materi::count(),
            'tugas' => $taId ? Tugas::whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $taId))->count() : Tugas::count(),
            'pengumpulan' => $taId
                ? TugasPengumpulan::whereHas('tugas.guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $taId))->count()
                : TugasPengumpulan::count(),
            'penilaian' => Penilaian::count(),
            'detailNilai' => $taId
                ? DetailPenilaian::where('tahun_ajaran_id', $taId)->count()
                : DetailPenilaian::count(),
            'siswaKelas' => $taId ? SiswaKelas::where('tahun_ajaran_id', $taId)->count() : SiswaKelas::count(),
            'users' => User::count(),
        ];
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{title: string, headers: string[], rows: array<int, array<int, mixed>>}
     */
    private function sheet(string $title, array $headers, array $rows): array
    {
        return ['title' => $title, 'headers' => $headers, 'rows' => $rows];
    }

    private function tahunAjaranId(): ?int
    {
        return $this->tahunAjaranId;
    }

    private function tanggal(null|string|\DateTimeInterface $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return Carbon::parse($value)->format('d-m-Y');
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function tahunAjaran(): array
    {
        return TahunAjaran::orderByDesc('active')
            ->orderBy('name')
            ->get()
            ->map(fn (TahunAjaran $t): array => [
                $t->name,
                $t->active ? 'Aktif' : 'Nonaktif',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function kelas(): array
    {
        $query = Kelas::with(['jurusan', 'walikelas'])->orderBy('nama');

        if ($this->tahunAjaranId()) {
            $query->whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $this->tahunAjaranId()));
        }

        return $query->get()
            ->map(fn (Kelas $k): array => [
                $k->nama,
                $k->tingkat ?? '-',
                $k->jurusan?->name ?? '-',
                $k->walikelas?->nama_lengkap ?? '-',
                $k->active ? 'Aktif' : 'Nonaktif',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function guru(): array
    {
        return Guru::orderBy('nama_lengkap')->get()
            ->map(fn (Guru $g): array => [
                $g->nip,
                $g->nama_lengkap,
                $g->pendidikan_terakhir ?? '-',
                $g->jenis_kelamin,
                $g->alamat ?? '-',
                $g->is_aktif ? 'Aktif' : 'Nonaktif',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function siswa(): array
    {
        if ($this->tahunAjaranId()) {
            return SiswaKelas::with(['siswa', 'kelas'])
                ->where('tahun_ajaran_id', $this->tahunAjaranId())
                ->orderBy('siswa_nisn')
                ->get()
                ->map(fn (SiswaKelas $sk): array => [
                    $sk->siswa_nisn,
                    $sk->siswa?->nis ?? '-',
                    $sk->siswa?->nama_lengkap ?? 'Siswa',
                    $sk->siswa?->tempat_lahir ?? '-',
                    $this->tanggal($sk->siswa?->tanggal_lahir),
                    $sk->siswa?->jenis_kelamin ?? '-',
                    $sk->siswa?->alamat ?? '-',
                    $sk->kelas?->nama ?? '-',
                    $sk->active ? 'Aktif' : 'Nonaktif',
                ])->all();
        }

        return Siswa::with('kelas')->orderBy('nama_lengkap')->get()
            ->map(fn (Siswa $s): array => [
                $s->nisn,
                $s->nis,
                $s->nama_lengkap,
                $s->tempat_lahir ?? '-',
                $this->tanggal($s->tanggal_lahir),
                $s->jenis_kelamin,
                $s->alamat ?? '-',
                $s->kelas?->nama ?? '-',
                $s->status,
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function penugasan(): array
    {
        $query = GuruKelas::with(['guru', 'kelas', 'matpel', 'tahunAjaran'])
            ->orderBy('kelas_id');

        if ($this->tahunAjaranId()) {
            $query->where('tahun_ajaran_id', $this->tahunAjaranId());
        }

        return $query->get()
            ->map(fn (GuruKelas $gk): array => [
                $gk->guru?->nama_lengkap ?? '-',
                $gk->kelas?->nama ?? '-',
                $gk->matpel?->name ?? '-',
                $gk->tahunAjaran?->name ?? '-',
                $gk->aktif ? 'Aktif' : 'Nonaktif',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function materi(): array
    {
        $query = Materi::with(['guru', 'guruKelas.kelas', 'guruKelas.matpel'])
            ->whereHas('guruKelas', fn ($q) => $q->where('aktif', true));

        if ($this->tahunAjaranId()) {
            $query->whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $this->tahunAjaranId()));
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn (Materi $m): array => [
                $m->judul,
                $m->guru?->nama_lengkap ?? '-',
                $m->guruKelas?->kelas?->nama ?? '-',
                $m->guruKelas?->matpel?->name ?? '-',
                $m->file_name ?? '-',
                $m->file_size ? round($m->file_size / 1024, 1) : '-',
                $m->created_at?->format('d-m-Y') ?? '-',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function tugas(): array
    {
        $query = Tugas::with(['guru', 'guruKelas.kelas', 'guruKelas.matpel'])
            ->whereHas('guruKelas', fn ($q) => $q->where('aktif', true));

        if ($this->tahunAjaranId()) {
            $query->whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $this->tahunAjaranId()));
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn (Tugas $t): array => [
                $t->judul,
                $t->guru?->nama_lengkap ?? '-',
                $t->guruKelas?->kelas?->nama ?? '-',
                $t->guruKelas?->matpel?->name ?? '-',
                $t->tanggal_terbit?->format('d-m-Y') ?? '-',
                $t->deadline?->format('d-m-Y H:i') ?? '-',
                $t->jenis_pengumpulan,
                $t->poin ?? '-',
                $t->file_name ?? '-',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function pengumpulan(): array
    {
        $query = TugasPengumpulan::with(['tugas', 'siswa']);

        if ($this->tahunAjaranId()) {
            $query->whereHas('tugas.guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $this->tahunAjaranId()));
        }

        return $query->orderByDesc('submitted_at')
            ->get()
            ->map(fn (TugasPengumpulan $p): array => [
                $p->tugas?->judul ?? '-',
                $p->siswa?->nama_lengkap ?? $p->siswa_nisn,
                $p->submitted_at?->format('d-m-Y H:i') ?? '-',
                $p->nilai ?? '-',
                $p->jawaban_teks ?? '-',
                $p->file_name ?? '-',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function penilaian(): array
    {
        return Penilaian::orderBy('nama')->get()
            ->map(fn (Penilaian $p): array => [
                $p->nama,
                $p->tipe,
                $p->nilai_maks,
                $p->bobot,
                $p->sumber ?? '-',
                $p->aktif ? 'Aktif' : 'Nonaktif',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function detailNilai(): array
    {
        $query = DetailPenilaian::with([
            'penilaian',
            'siswa',
            'guruKelas.kelas',
            'guruKelas.matpel',
            'tahunAjaran',
            'guru',
        ]);

        if ($this->tahunAjaranId()) {
            $query->where('tahun_ajaran_id', $this->tahunAjaranId());
        }

        return $query->orderBy('id')
            ->get()
            ->map(fn (DetailPenilaian $d): array => [
                $d->penilaian?->nama ?? '-',
                $d->siswa?->nama_lengkap ?? $d->siswa_nisn,
                $d->guruKelas?->kelas?->nama ?? '-',
                $d->guruKelas?->matpel?->name ?? '-',
                $d->tahunAjaran?->name ?? '-',
                $d->guru?->nama_lengkap ?? '-',
                $d->nilai,
                $d->keterangan ?? '-',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function siswaKelas(): array
    {
        $query = SiswaKelas::with(['siswa', 'kelas', 'tahunAjaran']);

        if ($this->tahunAjaranId()) {
            $query->where('tahun_ajaran_id', $this->tahunAjaranId());
        }

        return $query->orderByDesc('active')
            ->orderBy('siswa_nisn')
            ->get()
            ->map(fn (SiswaKelas $sk): array => [
                $sk->siswa?->nama_lengkap ?? $sk->siswa_nisn,
                $sk->kelas?->nama ?? '-',
                $sk->tahunAjaran?->name ?? '-',
                $sk->active ? 'Aktif' : 'Nonaktif',
                $sk->pertama_masuk ? 'Ya' : 'Tidak',
            ])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function users(): array
    {
        return User::with(['guru', 'siswa'])->orderBy('name')->get()
            ->map(fn (User $u): array => [
                $u->name,
                $u->email,
                ucfirst($u->role),
                $u->guru?->nama_lengkap ?? $u->siswa?->nama_lengkap ?? '-',
            ])->all();
    }

    // ── Reference data (tanpa filter tahun ajaran) ─────────────────────────

    /**
     * @return array<int, array<int, mixed>>
     */
    private function jurusan(): array
    {
        return Jurusan::orderBy('name')->get()
            ->map(fn (Jurusan $j): array => [$j->kode, $j->name])->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function matpel(): array
    {
        return Matpel::orderBy('name')->get()
            ->map(fn (Matpel $m): array => [$m->name, $m->description ?? '-'])->all();
    }
}
