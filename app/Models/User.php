<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_id
 * @property int|null $guru_id
 * @property string|null $nisn
 * @property bool $is_admin
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Siswa|null $siswa
 * @property-read Guru|null $guru
 * @property-read string|false $role
 * @property-read bool $gate_access
 */
#[Fillable(['name', 'email', 'is_admin', 'password', 'google_id', 'role', 'nisn', 'guru_id'])]
#[Hidden(['password', 'remember_token'])]
#[Appends('gate_access', 'role')]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the siswa record associated with the user.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nisn', 'nisn');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    protected function gateAccess(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => (($this->siswa !== null) || ($this->guru !== null)) || in_array($this->role, ['admin', 'panitia'], true)
        );
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if ($this->guru !== null) {
                    return 'guru';
                }

                if ($this->siswa !== null) {
                    return 'siswa';
                }

                if (($attributes['role'] ?? null) === 'admin') {
                    return 'admin';
                }

                if (($attributes['role'] ?? null) === 'panitia') {
                    return 'panitia';
                }

                return false;
            }
        );
    }

    /**
     * Determine whether the user has the given role.
     */
    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }
}
