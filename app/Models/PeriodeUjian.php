<?php

namespace App\Models;

use Database\Factories\PeriodeUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('cbt_periode_ujians')]
class PeriodeUjian extends Model
{
    protected $table = 'cbt_periode_ujians';
    /** @use HasFactory<PeriodeUjianFactory> */
    use HasFactory;

    protected $fillable = [
        'kategori',
        'tahun_ajaran_id',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function sedangAktif(): bool
    {
        $now = now();

        return $this->tanggal_mulai <= $now
            && ($this->tanggal_selesai === null || $this->tanggal_selesai >= $now);
    }

    public function sudahLewat(): bool
    {
        return $this->tanggal_selesai !== null && $this->tanggal_selesai < now();
    }

    public function akanDatang(): bool
    {
        return $this->tanggal_mulai !== null && $this->tanggal_mulai > now();
    }
}
