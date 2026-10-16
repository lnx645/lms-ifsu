<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    use HasFactory;

    protected $table = 'cbt_penilaian';

    protected $casts = [
        'nilai_maks' => 'float',
        'bobot' => 'float',
        'aktif' => 'boolean',
    ];

    protected $fillable = [
        'nama',
        'deskripsi',
        'tipe',
        'nilai_maks',
        'bobot',
        'aktif',
        'sumber',
        'tahun_ajaran_id',
        'semester',
        'ujian_id',
    ];

    /**
     * Ujian CBT sumber penilaian ini (jika dari CBT).
     */
    public function ujian()
    {
        return $this->belongsTo(Ujian::class);
    }

    /**
     * GuruKelas via ujian (untuk standar akademik).
     */
    public function guruKelas()
    {
        return $this->hasOneThrough(
            GuruKelas::class,
            Ujian::class,
            'id', // Foreign key di ujian
            'id', // Foreign key di guru_kelas
            'ujian_id', // Local key di penilaian
            'guru_kelas_id' // Local key di ujian
        );
    }

    /**
     * Daftar detail nilai (per siswa).
     */
    public function detailPenilaian()
    {
        return $this->hasMany(DetailPenilaian::class);
    }
}
