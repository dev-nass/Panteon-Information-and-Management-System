<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BurialRecord extends Model
{
    /** @use HasFactory<\Database\Factories\BurialRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'deceased_record_id',
        'lot_id',
        'user_id',
    ];

    public function deceasedRecord(): BelongsTo
    {
        return $this->belongsTo(DeceasedRecord::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
