<?php

namespace App\Models;

use Database\Factories\SoalFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cbt_soals')]
class Soal extends Model
{
    protected $table = 'cbt_soals';

    /** @use HasFactory<SoalFactory> */
    use HasFactory;

    protected $fillable = [
        'ujian_id',
        'paket_soal_id',
        'bank_soal_id',
        'tipe',
        'pertanyaan',
        'poin',
        'urutan',
        'kunci_isian',
        'isian_case_sensitive',
        'kode_kd',
        'indikator',
        'media_path',
        'media_type',
        'detail',
    ];

    protected $casts = [
        'poin' => 'integer',
        'urutan' => 'integer',
        'kunci_isian' => 'array',
        'isian_case_sensitive' => 'boolean',
        'detail' => 'array',
    ];

    /**
     * Tipe soal yang dinilai otomatis (objektif).
     *
     * @var array<int, string>
     */
    public const TIPE_OBJEKTIF = [
        'benar_salah',
        'pilihan_ganda', 'pilihan_ganda_kompleks', 'pilihan_ganda_citra',
        'menjodohkan', 'menjodohkan_banyak',
        'isian_singkat',
        'mengurutkan',
        'setuju_tidak',
        'matrix_text', 'matrix_checkbox',
        'mengelompokkan',
        'blank_space',
        'image_labeling',
    ];

    public const TIPE_ESAI = ['uraian', 'listening'];

    public const TIPE = [
        'benar_salah',
        'pilihan_ganda', 'pilihan_ganda_kompleks', 'pilihan_ganda_citra',
        'menjodohkan', 'menjodohkan_banyak',
        'isian_singkat',
        'mengurutkan',
        'setuju_tidak',
        'matrix_text', 'matrix_checkbox',
        'mengelompokkan',
        'blank_space',
        'image_labeling',
        'uraian', 'listening',
    ];

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class);
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function opsi(): HasMany
    {
        return $this->hasMany(OpsiSoal::class)->orderBy('urutan')->orderBy('id');
    }

    public function objektif(): bool
    {
        return in_array($this->tipe, self::TIPE_OBJEKTIF, true);
    }

    public function esai(): bool
    {
        return in_array($this->tipe, self::TIPE_ESAI, true);
    }

    public function butuhOpsi(): bool
    {
        return in_array($this->tipe, ['benar_salah', 'pilihan_ganda', 'pilihan_ganda_kompleks', 'pilihan_ganda_citra'], true);
    }
}
