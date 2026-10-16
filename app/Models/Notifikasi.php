<?php

namespace App\Models;

use Database\Factories\NotifikasiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    /** @use HasFactory<NotifikasiFactory> */
    use HasFactory;

    public const TIPE = ['info', 'jadwal', 'nilai', 'pengumuman'];

    protected $fillable = [
        'user_id',
        'tipe',
        'judul',
        'pesan',
        'tautan',
        'dibaca_at',
    ];

    protected $casts = [
        'dibaca_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('dibaca_at');
    }

    public function tandaiDibaca(): void
    {
        if ($this->dibaca_at === null) {
            $this->update(['dibaca_at' => now()]);
        }
    }
}
