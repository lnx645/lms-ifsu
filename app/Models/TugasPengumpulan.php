<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tugas_id', 'siswa_nisn', 'file_path', 'file_name', 'file_size', 'mime_type', 'jawaban_teks', 'submitted_at', 'nilai'])]
class TugasPengumpulan extends Model
{
    protected $table = 'tugas_pengumpulans';
    use HasFactory;

    protected $casts = [
        'submitted_at' => 'datetime',
        'nilai' => 'float',
    ];

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_nisn', 'nisn');
    }
}
