<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Applicant extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory;

    protected $fillable = [
        'deceased_record_id',
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
    ];

    public function deceasedRecord(): BelongsTo
    {
        return $this->belongsTo(DeceasedRecord::class);
    }
}
