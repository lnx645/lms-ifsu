<?php

namespace App\Models;

use Database\Factories\KelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nama
 * @property string|null $deskripsi
 * @property int|null $guru_id
 * @property bool $active
 * @property int|null $parent_id
 * @property int|null $jurusan_id
 * @property string|null $tingkat
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Guru|null $walikelas
 * @property-read Kelas|null $parent
 * @property-read Collection<int, Kelas> $children
 * @property-read Collection<int, Siswa> $siswas
 * @property-read Jurusan|null $jurusan
 * @property-read Collection<int, GuruKelas> $guruKelas
 */
#[Fillable(['nama', 'tingkat', 'deskripsi', 'guru_id', 'active', 'parent_id', 'jurusan_id'])]
class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory;

    /** @return BelongsTo<Guru, $this> */
    public function walikelas(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /** @return BelongsTo<Kelas, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Kelas, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope kelas riil (leaf): bukan root tingkat & bukan node jurusan.
     *
     * @param  Builder<static>  $query
     */
    public function scopeLeaf(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * Nama tingkat (X/XI/XII) yang dimiliki kelas ini, diturunkan dari root.
     */
    public function tingkatSekarang(): ?string
    {
        $node = $this;

        while ($node->parent_id !== null && $node->parent !== null) {
            $node = $node->parent;
        }

        return $node->tingkat ?? static::tingkatDariNama($this->nama);
    }

    /**
     * Ambil tingkat dari prefiks nama kelas (X, XI, XII), konvensi X-RPL-1 / XI-RPL-1.
     * Dipakai sebagai fallback saat root kelas tidak memiliki tingkat.
     */
    public static function tingkatDariNama(string $nama): ?string
    {
        return preg_match('/^(XII|XI|X)(?=[\s-]|$)/', trim($nama), $m) ? $m[1] : null;
    }

    /**
     * Tingkat berikutnya setelah tingkat saat ini (X -> XI, XI -> XII, XII -> null).
     */
    public static function tingkatBerikutnya(?string $tingkat): ?string
    {
        return match ($tingkat) {
            'X' => 'XI',
            'XI' => 'XII',
            default => null,
        };
    }

    /**
     * Cari kelas target untuk promosi: nama dengan tingkat berikutnya
     * (X-RPL-1 -> XI-RPL-1). Mengembalikan null jika tingkat berikutnya
     * tidak tersedia (mis. XII = lulus) atau target belum dibuat.
     */
    public function promoteTarget(): ?self
    {
        $tingkat = $this->tingkatSekarang();
        $tingkatTarget = self::tingkatBerikutnya($tingkat);

        if ($tingkatTarget === null) {
            return null;
        }

        $targetNama = preg_replace(
            '/^'.preg_quote($tingkat, '/').'/',
            $tingkatTarget,
            $this->nama,
            1,
        );

        if ($targetNama === $this->nama) {
            return null;
        }

        $candidates = collect([$targetNama, str_replace(' ', '-', $targetNama)])
            ->filter()
            ->unique()
            ->values();

        foreach ($candidates as $candidate) {
            $target = self::query()->where('nama', $candidate)->first();

            if ($target) {
                return $target;
            }
        }

        return null;
    }

    /** @return HasManyThrough<Siswa, SiswaKelas, $this> */
    public function siswas(): HasManyThrough
    {
        return $this->hasManyThrough(Siswa::class, SiswaKelas::class, 'kelas_id', 'nisn', 'id', 'siswa_nisn');
    }

    /** @return BelongsTo<Jurusan, $this> */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    /** @return HasMany<GuruKelas, $this> */
    public function guruKelas(): HasMany
    {
        return $this->hasMany(GuruKelas::class);
    }
}
