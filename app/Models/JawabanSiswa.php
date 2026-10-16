<?php

namespace App\Models;

use Database\Factories\JawabanSiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('cbt_jawaban_siswas')]
class JawabanSiswa extends Model
{
    protected $table = 'cbt_jawaban_siswas';
    /** @use HasFactory<JawabanSiswaFactory> */
    use HasFactory;

    protected $fillable = [
        'ujian_pengerjaan_id',
        'soal_id',
        'opsi_dipilih',
        'jawaban_teks',
        'benar',
        'skor',
        'ragu',
    ];

    protected $casts = [
        'opsi_dipilih' => 'array',
        'benar' => 'boolean',
        'skor' => 'float',
        'ragu' => 'boolean',
    ];

    public function pengerjaan(): BelongsTo
    {
        return $this->belongsTo(UjianPengerjaan::class, 'ujian_pengerjaan_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
