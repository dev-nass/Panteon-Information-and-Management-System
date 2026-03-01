<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BurialRecord extends Model
{
    /** @use HasFactory<\Database\Factories\BurialRecordFactory> */
    use HasFactory;

    public function deceasedRecord(): BelongsTo
    {
        return $this->belongsTo(DeceasedRecord::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
