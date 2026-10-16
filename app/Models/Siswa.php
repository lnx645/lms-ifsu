<?php

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

/**
 * @property string $nisn
 * @property string|null $nis
 * @property string $nama_lengkap
 * @property string|null $tempat_lahir
 * @property string|null $tanggal_lahir
 * @property 'L'|'P'|null $jenis_kelamin
 * @property string|null $alamat
 * @property string|null $foto_profil
 * @property bool $is_aktif
 * @property string|null $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, SiswaKelas> $siswaKelas
 * @property-read Kelas|null $kelas
 */
#[Fillable(['nisn', 'nis', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'foto_profil', 'is_aktif', 'status'])]
#[Table('siswa')]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory;

    protected $primaryKey = 'nisn';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'status' => 'string',
    ];

    protected $hidden = [
        'password_ujian',
    ];

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'nisn', 'nisn');
    }

    /** @return HasMany<SiswaKelas, $this> */
    public function siswaKelas(): HasMany
    {
        return $this->hasMany(SiswaKelas::class, 'siswa_nisn', 'nisn');
    }

    /**
     * Kelas yang sedang aktif diikuti siswa (riwayat lengkap ada di siswaKelas()).
     *
     * @return HasOneThrough<Kelas, SiswaKelas, $this>
     */
    public function kelas(): HasOneThrough
    {
        return $this->hasOneThrough(Kelas::class, SiswaKelas::class, 'siswa_nisn', 'id', 'nisn', 'kelas_id')
            ->where('siswa_kelas.active', true);
    }
}
