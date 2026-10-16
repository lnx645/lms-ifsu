<?php

namespace App\Models;

use Database\Factories\BeritaAcaraUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('cbt_berita_acara_ujians')]
class BeritaAcaraUjian extends Model
{
    protected $table = 'cbt_berita_acara_ujians';
    /** @use HasFactory<BeritaAcaraUjianFactory> */
    use HasFactory;

    protected $fillable = [
        'ujian_id',
        'sesi_ujian_id',
        'tanggal',
        'nomor_surat',
        'jumlah_peserta',
        'jumlah_hadir',
        'jumlah_tidak_hadir',
        'jumlah_pelanggaran',
        'catatan',
        'nama_pengawas',
        'nama_kepala_sekolah',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_peserta' => 'integer',
        'jumlah_hadir' => 'integer',
        'jumlah_tidak_hadir' => 'integer',
        'jumlah_pelanggaran' => 'integer',
    ];

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id');
    }
}
