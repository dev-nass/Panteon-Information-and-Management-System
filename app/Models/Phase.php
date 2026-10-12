<?php

namespace App\Models;

use Database\Factories\PhaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phase extends Model
{
    /** @use HasFactory<PhaseFactory> */
    use HasFactory;

    protected $fillable = [
        'phase_name',
        'coordinates',
    ];

    protected $hidden = [
        'coordinates',
    ];

    public function clusters(): HasMany
    {
        return $this->hasMany(Cluster::class);
    }
}
