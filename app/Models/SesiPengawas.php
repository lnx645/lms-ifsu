<?php

namespace App\Models;

use Database\Factories\SesiPengawasFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('cbt_sesi_pengawas')]
class SesiPengawas extends Model
{
    protected $table = 'cbt_sesi_pengawas';
    /** @use HasFactory<SesiPengawasFactory> */
    use HasFactory;

    public const PERAN = ['pengawas', 'penanggung_jawab'];

    protected $fillable = [
        'sesi_ujian_id',
        'guru_id',
        'nama_pengawas',
        'peran',
    ];

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }
}
