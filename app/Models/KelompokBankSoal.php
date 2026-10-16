<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CBT v2: Kelompok Bank Soal — wadah bernama berisi kumpulan soal.
 *
 * Alur: guru buat bank (ketik nama di modal) -> isi soal ke dalamnya ->
 * soal dipakai untuk generate paket ujian.
 */
class KelompokBankSoal extends Model
{
    protected $table = 'cbt_kelompok_bank_soals';
    protected $fillable = ['nama', 'deskripsi', 'matpel_id', 'guru_id', 'kode', 'urutan_tampil', 'komposisi'];

    protected $casts = [
        'komposisi' => 'array',
    ];

    /**
     * Tipe soal ala Extraordinary CBT.
     */
    public const TIPE_SOAL = [
        'pilihan_ganda' => 'Pilihan ganda',
        'pilihan_ganda_kompleks' => 'Pilihan ganda kompleks',
        'pilihan_ganda_citra' => 'Pilihan ganda citra',
        'menjodohkan' => 'Menjodohkan',
        'menjodohkan_banyak' => 'Menjodohkan -> Banyak',
        'isian_singkat' => 'Isian singkat',
        'uraian' => 'Uraian',
        'mengurutkan' => 'Mengurutkan',
        'benar_salah' => 'Benar/salah',
        'setuju_tidak' => 'Setuju/tidak',
        'matrix_text' => 'Table matrix input text',
        'matrix_checkbox' => 'Table matrix checkbox',
        'mengelompokkan' => 'Mengelompokkan',
        'blank_space' => 'Put blank space',
        'image_labeling' => 'Image labeling',
        'listening' => 'Listening',
    ];

    public function matpel(): BelongsTo
    {
        return $this->belongsTo(Matpel::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function soals(): HasMany
    {
        return $this->hasMany(BankSoal::class)->orderBy('id');
    }
}
