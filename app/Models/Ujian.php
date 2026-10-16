<?php

namespace App\Models;

use Database\Factories\UjianFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Table('cbt_ujians')]
class Ujian extends Model
{
    protected $table = 'cbt_ujians';

    /** @use HasFactory<UjianFactory> */
    use HasFactory;

    protected $fillable = [
        'guru_id',
        'guru_kelas_id',
        'penilaian_id',
        'dibuat_oleh_admin',
        'kategori',
        'judul',
        'deskripsi',
        'tata_tertib',
        'pool_soal',
        'tanggal_mulai',
        'tanggal_selesai',
        'durasi_menit',
        'maks_attempt',
        'acak_soal',
        'acak_opsi',
        'tampilkan_hasil',
        'wajib_fullscreen',
        'maks_pelanggaran',
        'token',
        'token_released_at',
        'token_pengelola',
        'mode_sesi',
        'nilai_maks',
        'kkm',
        'bobot',
        'status',
        'kartu_diterbitkan',
        'catatan_panitia', 'event_id', 'kelompok_bank_soal_id'];

    protected $casts = [
        'dibuat_oleh_admin' => 'boolean',
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'durasi_menit' => 'integer',
        'maks_attempt' => 'integer',
        'pool_soal' => 'array',
        'acak_soal' => 'boolean',
        'acak_opsi' => 'boolean',
        'tampilkan_hasil' => 'boolean',
        'wajib_fullscreen' => 'boolean',
        'maks_pelanggaran' => 'integer',
        'token_released_at' => 'datetime',
        'nilai_maks' => 'integer',
        'kkm' => 'integer',
        'bobot' => 'integer',
    ];

    /**
     * Kategori yang tergolong ujian besar (butuh window jadwal & proctoring ketat).
     *
     * @var array<int, string>
     */
    public const KATEGORI_BESAR = ['uts', 'uas', 'usbk'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function guruKelas(): BelongsTo
    {
        return $this->belongsTo(GuruKelas::class);
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class);
    }

    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class)->orderBy('urutan')->orderBy('id');
    }

    public function pengerjaans(): HasMany
    {
        return $this->hasMany(UjianPengerjaan::class);
    }

    /**
     * Paket soal (varian A/B/C) — CBT v2 untuk ujian resmi.
     */
    public function paketSoals(): HasMany
    {
        return $this->hasMany(PaketSoal::class)->orderBy('kode');
    }

    /**
     * Apakah ujian ini memakai paket soal (mode resmi).
     */
    public function memakaiPaketSoal(): bool
    {
        return $this->paketSoals()->exists();
    }

    /**
     * Gelombang sesi ujian (standar nasional). Kosong = tanpa sesi.
     */
    public function sesis(): HasMany
    {
        return $this->hasMany(SesiUjian::class)->orderBy('urutan')->orderBy('id');
    }

    public function beritaAcaras(): HasMany
    {
        return $this->hasMany(BeritaAcaraUjian::class);
    }

    /**
     * Siswa butuh remedial bila nilai terbaiknya di bawah KKM.
     */
    public function butuhRemedial(string $nisn): bool
    {
        if ($this->kkm === null) {
            return false;
        }

        $terbaik = $this->pengerjaans()
            ->where('siswa_nisn', $nisn)
            ->where('is_remedial', false)
            ->whereIn('status', ['selesai', 'auto_submit'])
            ->max('nilai_total');

        return $terbaik !== null && $terbaik < $this->kkm;
    }

    public function memakaiSesi(): bool
    {
        return $this->sesis()->exists();
    }

    /**
     * Mode 'terjadwal': siswa hanya boleh masuk sesi yang dijatahkan (ala USBK).
     * Mode 'bebas': siswa boleh masuk sesi aktif mana saja.
     */
    public function sesiTerjadwal(): bool
    {
        return $this->mode_sesi === 'terjadwal';
    }

    /**
     * Sesi yang sedang aktif sekarang, null bila tidak ada.
     */
    public function sesiAktif(): ?SesiUjian
    {
        $now = Carbon::now();

        /** @var SesiUjian|null $sesi */
        $sesi = $this->sesis()
            ->where('waktu_mulai', '<=', $now)
            ->where('waktu_selesai', '>=', $now)
            ->first();

        return $sesi;
    }

    public function scopeBesar(Builder $query): Builder
    {
        return $query->whereIn('kategori', self::KATEGORI_BESAR);
    }

    public function kategoriBesar(): bool
    {
        return in_array($this->kategori, self::KATEGORI_BESAR, true);
    }

    public function sudahTerbit(): bool
    {
        return $this->status === 'terbit';
    }

    public function sudahMulai(): bool
    {
        return $this->tanggal_mulai === null || $this->tanggal_mulai->lte(Carbon::now());
    }

    public function sudahSelesai(): bool
    {
        return $this->tanggal_selesai !== null && $this->tanggal_selesai->lt(Carbon::now());
    }

    public function sedangBerlangsung(): bool
    {
        return $this->sudahTerbit() && $this->sudahMulai() && ! $this->sudahSelesai();
    }

    /**
     * Total poin semua soal (dipakai untuk normalisasi nilai ke nilai_maks).
     */
    public function totalPoin(): int
    {
        return (int) $this->soals()->sum('poin');
    }

    /**
     * Token valid: cocok dan sudah dirilis.
     *
     * Catatan: pengecekan "ujian sedang berlangsung" TIDAK dilakukan di sini,
     * melainkan di pemanggil (mis. Siswa\UjianController::mulai via sedangBerlangsung()).
     */
    public function tokenValid(?string $token): bool
    {
        if ($this->token === null) {
            return true;
        }

        if ($this->token_released_at === null) {
            return false;
        }

        return $token !== null && strcasecmp($token, $this->token) === 0;
    }
}
