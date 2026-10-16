<?php

namespace App\Models;

use Database\Factories\RuangUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cbt_ruang_ujians')]
class RuangUjian extends Model
{
    protected $table = 'cbt_ruang_ujians';
    /** @use HasFactory<RuangUjianFactory> */
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'kapasitas',
        'lokasi',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
    ];

    public function sesis(): HasMany
    {
        return $this->hasMany(SesiUjian::class);
    }
}
