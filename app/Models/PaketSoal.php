<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Paket soal (varian A/B/C) untuk ujian resmi CBT v2.
 *
 * Satu ujian resmi memiliki beberapa paket; setiap paket berisi
 * susunan soal berbeda dari bank soal yang sama. Siswa mendapat
 * paket berbeda dari teman sebangkunya (distribusi round-robin).
 */
class PaketSoal extends Model
{
    protected $table = 'cbt_paket_soals';
    protected $fillable = ['ujian_id', 'kode', 'nama'];

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class)->orderBy('urutan');
    }

    public function pengerjaans(): HasMany
    {
        return $this->hasMany(UjianPengerjaan::class);
    }
}
