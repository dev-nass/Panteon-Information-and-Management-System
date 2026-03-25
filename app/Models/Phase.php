<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phase extends Model
{
    /** @use HasFactory<\Database\Factories\PhaseFactory> */
    use HasFactory;

    public function cluster(): HasMany
    {
        return $this->hasMany(Cluster::class);
    }
}
