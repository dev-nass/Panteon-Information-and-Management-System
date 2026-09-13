<?php

namespace App\Models;

use Database\Factories\BurialRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BurialRecord extends Model
{
    /** @use HasFactory<BurialRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'deceased_record_id',
        'lot_id',
        'user_id',
        'archived_at',
        'archived_reason',
        'archived_by',
        'archived_notes',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
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

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}
