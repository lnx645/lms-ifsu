<?php

namespace App\Models;

use Database\Factories\MatpelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description'])]
class Matpel extends Model
{
    /** @use HasFactory<MatpelFactory> */
    use HasFactory;

    public function bankSoals(): HasMany
    {
        return $this->hasMany(BankSoal::class, 'matpel_id');
    }

    public function guruKelas(): HasMany
    {
        return $this->hasMany(GuruKelas::class, 'matpel_id');
    }
}
