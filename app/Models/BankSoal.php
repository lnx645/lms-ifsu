<?php

namespace App\Models;

use Database\Factories\BankSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cbt_bank_soals')]
class BankSoal extends Model
{
    protected $table = 'cbt_bank_soals';
    /** @use HasFactory<BankSoalFactory> */
    use HasFactory;

    protected $fillable = [
        'kelompok_bank_soal_id',
        'matpel_id',
        'guru_id',
        'tipe',
        'pertanyaan',
        'poin',
        'topik',
        'kesulitan',
        'kunci_isian',
        'detail',
        'isian_case_sensitive',
        'kode_kd',
        'indikator',
        'media_path',
        'media_type',
    ];

    protected $casts = [
        'detail' => 'array',
        'poin' => 'integer',
        'kunci_isian' => 'array',
        'isian_case_sensitive' => 'boolean',
    ];

    public const KESULITAN = ['mudah', 'sedang', 'sulit'];

    /** Tipe soal yang butuh opsi jawaban (bukan isian/esai). */
    public const OPSI_TIPE = ['pg', 'multi', 'benar_salah'];

    public function matpel(): BelongsTo
    {
        return $this->belongsTo(Matpel::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(KelompokBankSoal::class, 'kelompok_bank_soal_id');
    }

    public function opsi(): HasMany
    {
        return $this->hasMany(BankOpsiSoal::class)->orderBy('urutan')->orderBy('id');
    }

    public function butuhOpsi(): bool
    {
        return in_array($this->tipe, self::OPSI_TIPE, true);
    }

    /**
     * @param  array<int, int>  $matpelIds
     */
    public function scopeUntukMatpel(Builder $query, array $matpelIds): Builder
    {
        return $query->whereIn('matpel_id', $matpelIds);
    }

    public function getTipeLabelAttribute(): string
    {
        return \App\Models\KelompokBankSoal::TIPE_SOAL[$this->tipe] ?? $this->tipe;
    }

    protected $appends = ['tipe_label'];
}
