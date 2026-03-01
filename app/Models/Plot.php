<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plot extends Model
{
    /** @use HasFactory<\Database\Factories\PlotFactory> */
    use HasFactory;

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
