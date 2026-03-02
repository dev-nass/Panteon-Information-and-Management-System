<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    /** @use HasFactory<\Database\Factories\LotFactory> */
    use HasFactory;

    protected $casts = [
        'coordinates' => 'array', // or 'json'
    ];

    protected $fillable = [
        'section_id',
        'lot_type',
        'total_capacity',
        'coordinates',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function burialRecords(): HasMany
    {
        return $this->hasMany(BurialRecord::class);
    }

    // TODO: create function that counts how many times each plot
    // appeared on burial_records
}
