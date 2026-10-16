<?php

namespace App\Models;

use Database\Factories\SesiUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cbt_sesi_ujians')]
class SesiUjian extends Model
{
    protected $table = 'cbt_sesi_ujians';

    /** @use HasFactory<SesiUjianFactory> */
    use HasFactory;

    protected $fillable = [
        'ujian_id',
        'nama',
        'urutan',
        'waktu_mulai',
        'waktu_selesai',
        'kapasitas',
        'terisi',
        'ruang_ujian_id',
        'token',
        'token_released_at',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'kapasitas' => 'integer',
        'token_released_at' => 'datetime',
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    public function pengerjaans(): HasMany
    {
        return $this->hasMany(UjianPengerjaan::class);
    }

    /**
     * Penjadwalan siswa ke sesi ini (mode_sesi = 'terjadwal', ala USBK).
     */
    public function pesertas(): HasMany
    {
        return $this->hasMany(SesiUjianPeserta::class, 'sesi_ujian_id');
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(RuangUjian::class, 'ruang_ujian_id');
    }

    public function pengawas(): HasMany
    {
        return $this->hasMany(SesiPengawas::class, 'sesi_ujian_id');
    }

    public function beritaAcara(): HasMany
    {
        return $this->hasMany(BeritaAcaraUjian::class, 'sesi_ujian_id');
    }

    /**
     * Cari sesi yang dijatahkan untuk siswa pada ujian ini.
     */
    public static function jatahUntuk(int $ujianId, string $nisn): ?self
    {
        $peserta = SesiUjianPeserta::where('ujian_id', $ujianId)
            ->where('siswa_nisn', $nisn)
            ->first();

        return $peserta?->sesi;
    }

    public function sedangAktif(): bool
    {
        $now = now();

        return $this->waktu_mulai <= $now && $this->waktu_selesai >= $now;
    }

    public function sudahLewat(): bool
    {
        return $this->waktu_selesai < now();
    }

    /**
     * Jumlah slot tersisa; null berarti tanpa batas kapasitas.
     */
    public function sisaKapasitas(): ?int
    {
        if ($this->kapasitas === null) {
            return null;
        }

        $terisi = $this->pengerjaans()
            ->where('status', 'berlangsung')
            ->count();

        return max(0, $this->kapasitas - $terisi);
    }

    public function penuh(): bool
    {
        return $this->sisaKapasitas() === 0;
    }
}
