<?php

namespace App\Models;

use Database\Factories\GuruFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $nip
 * @property string $nama_lengkap
 * @property 'L'|'P'|null $jenis_kelamin
 * @property string|null $alamat
 * @property string|null $foto_profil
 * @property bool $is_aktif
 * @property string|null $pendidikan_terakhir
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, Kelas> $kelas
 * @property-read Collection<int, Kelas> $walikelas
 * @property-read Collection<int, GuruKelas> $guruKelas
 */
#[Fillable(['nip', 'nama_lengkap', 'pendidikan_terakhir', 'jenis_kelamin', 'alamat', 'foto_profil', 'is_aktif'])]
class Guru extends Model
{
    /** @use HasFactory<GuruFactory> */
    use HasFactory;

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'guru_id');
    }

    /** @return HasMany<Kelas, $this> */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /** @return HasMany<Kelas, $this> */
    public function walikelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'guru_id');
    }

    /** @return HasMany<GuruKelas, $this> */
    public function guruKelas(): HasMany
    {
        return $this->hasMany(GuruKelas::class);
    }

    // Di dalam model Guru.php

    /** @return HasManyThrough<Kelas, GuruKelas, $this> */
    public function mengajar(): HasManyThrough
    {
        return $this->hasManyThrough(
            Kelas::class,
            GuruKelas::class,
            'guru_id',
            'id',
            'id',
            'kelas_id'
        );
    }
}
