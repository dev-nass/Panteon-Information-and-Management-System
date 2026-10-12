<?php

namespace App\Models;

use Database\Factories\PhaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends Model
{
    /** @use HasFactory<PhaseFactory> */
    use HasFactory;

    protected $fillable = [
        'phase_id',
        'cluster_name',
        'cluster_type',
        'total_capacity',
        'coordinates',
    ];

    protected $hidden = [
        'coordinates',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}
