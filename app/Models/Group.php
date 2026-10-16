<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $table = 'cbt_groups';

    protected $fillable = ['nama', 'deskripsi', 'matpel_id', 'guru_id'];

    /**
     * Sub-group dari kelas.
     *
     * @return BelongsToMany<Kelas, $this>
     */
    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'cbt_group_kelas');
    }

    public function matpel(): BelongsTo
    {
        return $this->belongsTo(Matpel::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }
}
