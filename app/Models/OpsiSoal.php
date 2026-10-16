<?php

namespace App\Models;

use Database\Factories\OpsiSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('cbt_opsi_soals')]
class OpsiSoal extends Model
{
    protected $table = 'cbt_opsi_soals';
    /** @use HasFactory<OpsiSoalFactory> */
    use HasFactory;

    protected $fillable = [
        'soal_id',
        'teks',
        'benar',
        'urutan',
    ];

    protected $casts = [
        'benar' => 'boolean',
        'urutan' => 'integer',
    ];

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
