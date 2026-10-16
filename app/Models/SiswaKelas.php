<?php

namespace App\Models;

use Database\Factories\SiswaKelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_nisn', 'kelas_id', 'tahun_ajaran_id', 'active', 'pertama_masuk'])]
class SiswaKelas extends Model
{
    /** @use HasFactory<SiswaKelasFactory> */
    use HasFactory;

    protected $casts = [
        'active' => 'boolean',
        'pertama_masuk' => 'boolean',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_nisn', 'nisn');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
