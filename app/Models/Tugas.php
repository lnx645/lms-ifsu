<?php

namespace App\Models;

use Database\Factories\TugasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $guru_id
 * @property int|null $guru_kelas_id
 * @property string $judul
 * @property string|null $deskripsi
 * @property Carbon|null $tanggal_terbit
 * @property Carbon|null $deadline
 * @property string|null $file_path
 * @property string|null $file_name
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property string $jenis_pengumpulan
 * @property int $poin
 * @property int|null $penilaian_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Guru|null $guru
 * @property-read GuruKelas|null $guruKelas
 * @property-read Penilaian|null $penilaian
 * @property-read Collection<int, TugasPengumpulan> $pengumpulans
 */
#[Fillable(['guru_id', 'guru_kelas_id', 'judul', 'deskripsi', 'tanggal_terbit', 'deadline', 'jenis_pengumpulan', 'file_path', 'file_name', 'file_size', 'mime_type', 'poin', 'penilaian_id'])]
class Tugas extends Model
{
    protected $table = 'tugases';
    /** @use HasFactory<TugasFactory> */
    use HasFactory;

    protected $casts = [
        'tanggal_terbit' => 'datetime',
        'deadline' => 'datetime',
        'poin' => 'integer',
    ];

    /** @return BelongsTo<Guru, $this> */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    /** @return BelongsTo<GuruKelas, $this> */
    public function guruKelas(): BelongsTo
    {
        return $this->belongsTo(GuruKelas::class);
    }

    /** @return BelongsTo<Penilaian, $this> */
    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class);
    }

    /** @return HasMany<TugasPengumpulan, $this> */
    public function pengumpulans(): HasMany
    {
        return $this->hasMany(TugasPengumpulan::class);
    }

    /**
     * Tugas sudah bisa dilihat siswa (tanggal terbit tiba atau tidak diatur).
     */
    public function sudahTerbit(): bool
    {
        return $this->tanggal_terbit === null || $this->tanggal_terbit->lte(Carbon::now());
    }

    /**
     * Batas waktu pengumpulan sudah lewat.
     */
    public function sudahLewatDeadline(): bool
    {
        return $this->deadline !== null && $this->deadline->lt(Carbon::now());
    }
}
