<?php

namespace App\Models;

use Database\Factories\PelanggaranLogFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('cbt_pelanggaran_logs')]
class PelanggaranLog extends Model
{
    protected $table = 'cbt_pelanggaran_logs';
    /** @use HasFactory<PelanggaranLogFactory> */
    use HasFactory;

    protected $fillable = [
        'ujian_pengerjaan_id',
        'jenis',
        'terjadi_at',
    ];

    protected $casts = [
        'terjadi_at' => 'datetime',
    ];

    public const JENIS = ['blur', 'exit_fullscreen', 'tanpa_fullscreen'];

    public function pengerjaan(): BelongsTo
    {
        return $this->belongsTo(UjianPengerjaan::class, 'ujian_pengerjaan_id');
    }
}
