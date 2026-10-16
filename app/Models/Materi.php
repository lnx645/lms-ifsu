<?php

namespace App\Models;

use Database\Factories\MateriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['guru_id', 'guru_kelas_id', 'judul', 'deskripsi', 'konten', 'file_path', 'file_name', 'file_size', 'mime_type'])]
class Materi extends Model
{
    /** @use HasFactory<MateriFactory> */
    use HasFactory;

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function guruKelas(): BelongsTo
    {
        return $this->belongsTo(GuruKelas::class);
    }
}
