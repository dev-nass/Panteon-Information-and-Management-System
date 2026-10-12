<?php

namespace App\Models;

use Database\Factories\LotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    /** @use HasFactory<LotFactory> */
    use HasFactory;

    // Add this:
    protected $casts = [
    ];

    protected $fillable = [
        'cluster_id',
        'column',
        'row',
        'coordinates',
    ];

    protected $hidden = [
        'coordinates',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function burialRecords(): HasMany
    {
        return $this->hasMany(BurialRecord::class);
    }

    // TODO: create function that counts how many times each plot
    // appeared on burial_records
}
