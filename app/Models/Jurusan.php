<?php

namespace App\Models;

use Database\Factories\JurusanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $kode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Kelas> $kelas
 */
#[Fillable(['name', 'kode'])]
class Jurusan extends Model
{
    /** @use HasFactory<JurusanFactory> */
    use HasFactory;

    /**
     * Query seluruh siswa yang kelas aktifnya berada di jurusan ini.
     *
     * @return Builder<Siswa>
     */
    public function siswas(): Builder
    {
        return Siswa::query()->whereHas('kelas', fn ($query) => $query->where('jurusan_id', $this->id));
    }

    /** @return HasMany<Kelas, $this> */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
