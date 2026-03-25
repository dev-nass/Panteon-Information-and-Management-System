<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends Model
{
    /** @use HasFactory<\Database\Factories\PhaseFactory> */
    use HasFactory;

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}
