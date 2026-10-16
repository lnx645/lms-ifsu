<?php

namespace App\Models;

use Database\Factories\SesiUjianPesertaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesiUjianPeserta extends Model
{
    protected $table = 'cbt_sesi_ujian_pesertas';
    /** @use HasFactory<SesiUjianPesertaFactory> */
    use HasFactory;

    protected $fillable = [
        'sesi_ujian_id',
        'ujian_id',
        'siswa_nisn',
    ];

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id');
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_nisn', 'nisn');
    }
}
