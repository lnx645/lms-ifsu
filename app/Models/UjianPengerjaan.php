<?php

namespace App\Models;

use Database\Factories\UjianPengerjaanFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Table('cbt_ujian_pengerjaans')]
class UjianPengerjaan extends Model
{
    protected $table = 'cbt_ujian_pengerjaans';
    /** @use HasFactory<UjianPengerjaanFactory> */
    use HasFactory;

    protected $fillable = [
        'ujian_id',
        'siswa_nisn',
        'sesi_ujian_id',
        'paket_soal_id',
        'attempt_ke',
        'mulai_at',
        'batas_at',
        'submitted_at',
        'status',
        'nilai_objektif',
        'nilai_esai',
        'nilai_total',
        'jumlah_pelanggaran',
        'is_remedial',
    ];

    protected $casts = [
        'attempt_ke' => 'integer',
        'mulai_at' => 'datetime',
        'batas_at' => 'datetime',
        'submitted_at' => 'datetime',
        'nilai_objektif' => 'float',
        'nilai_esai' => 'float',
        'nilai_total' => 'float',
        'jumlah_pelanggaran' => 'integer',
        'is_remedial' => 'boolean',
    ];

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    /**
     * Sesi gelombang tempat pengerjaan ini dimulai (null bila tanpa sesi).
     */
    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id');
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_nisn', 'nisn');
    }

    public function jawabans(): HasMany
    {
        return $this->hasMany(JawabanSiswa::class);
    }


    public function sedangBerlangsung(): bool
    {
        return $this->status === 'berlangsung';
    }

    public function waktuHabis(): bool
    {
        return $this->batas_at->lt(Carbon::now());
    }

    /**
     * Sisa detik pengerjaan (0 jika habis).
     */
    public function sisaDetik(): int
    {
        return max(0, Carbon::now()->diffInSeconds($this->batas_at, false));
    }
}
